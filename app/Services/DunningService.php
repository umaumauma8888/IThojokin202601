<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\DunningRecord;
use App\Models\DunningSchedule;
use App\Notifications\DunningNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

/**
 * 督促管理サービス
 *
 * インボイス対応類型「決済機能」対応：
 * 売掛金の未回収債権に対する督促アクションを自動化し、
 * 債権債務管理業務の負担を解消する
 */
class DunningService
{
    public function __construct(
        private readonly PaymentMatchingService $matchingService
    ) {}

    /**
     * 督促が必要な請求書を自動抽出してメール送信（日次バッチ用）
     *
     * @return array 実行結果サマリー
     */
    public function processAutoDunning(): array
    {
        $schedules = DunningSchedule::where('is_active', true)
                                    ->orderBy('days_after_due')
                                    ->get();

        $results = ['sent' => 0, 'skipped' => 0, 'errors' => 0];

        foreach ($schedules as $schedule) {
            $targetDate = today()->subDays($schedule->days_after_due);

            $invoices = Invoice::with(['customer', 'dunningRecords'])
                ->where('collection_status', 'overdue')
                ->where('due_date', '<=', $targetDate)
                ->where('receivable_balance', '>', 0)
                ->whereDoesntHave('dunningRecords', function ($q) use ($schedule) {
                    $q->where('dunning_type', $schedule->dunning_type);
                })
                ->get();

            foreach ($invoices as $invoice) {
                try {
                    $this->sendDunning($invoice, $schedule);
                    $results['sent']++;
                } catch (\Exception $e) {
                    Log::error('督促送信エラー', [
                        'invoice_id' => $invoice->id,
                        'error'      => $e->getMessage(),
                    ]);
                    $results['errors']++;
                }
            }
        }

        Log::info('自動督促処理完了', $results);
        return $results;
    }

    /**
     * 個別督促の実行
     */
    public function sendDunning(Invoice $invoice, DunningSchedule|array $schedule): DunningRecord
    {
        return DB::transaction(function () use ($invoice, $schedule) {
            $scheduleData = is_array($schedule) ? $schedule : $schedule->toArray();

            // 督促記録の作成
            $record = DunningRecord::create([
                'invoice_id'       => $invoice->id,
                'customer_id'      => $invoice->customer_id,
                'dunning_type'     => $scheduleData['dunning_type'],
                'method'           => $scheduleData['method'],
                'dunning_date'     => today(),
                'next_action_date' => today()->addDays(7),
                'content'          => $this->buildDunningContent($invoice, $scheduleData),
                'result'           => 'pending',
                'handled_by'       => auth()->id() ?? 1,
            ]);

            // メール送信
            if ($scheduleData['method'] === 'email') {
                $invoice->customer->notify(
                    new DunningNotification($invoice, $record, $scheduleData['dunning_type'])
                );
            }

            // 請求書ステータスを「督促中」に更新
            $invoice->update([
                'collection_status' => 'dunning',
                'last_dunning_at'   => now(),
            ]);

            Log::info('督促実行', [
                'invoice_id'   => $invoice->id,
                'dunning_type' => $scheduleData['dunning_type'],
                'customer'     => $invoice->customer->company_name,
            ]);

            return $record;
        });
    }

    /**
     * 督促内容の自動生成
     */
    private function buildDunningContent(Invoice $invoice, array $schedule): string
    {
        $overdueDays = Carbon::parse($invoice->due_date)->diffInDays(today());
        $balance     = number_format($invoice->receivable_balance);

        $messages = [
            'first'  => "お支払期日（{$invoice->due_date}）より{$overdueDays}日が経過しております。¥{$balance}のお支払いを確認できておりません。",
            'second' => "再度のご連絡となります。¥{$balance}の未払いが{$overdueDays}日超過しております。至急ご対応をお願いいたします。",
            'final'  => "最終督促です。¥{$balance}の未払いが継続している場合、法的措置を検討させていただきます。",
            'legal'  => "弁護士を通じた法的措置の手続きを開始します。",
        ];

        return $messages[$schedule['dunning_type']] ?? $messages['first'];
    }

    /**
     * 督促ステータスの更新（電話・訪問後の結果記録）
     */
    public function updateDunningResult(DunningRecord $record, string $result, string $response): void
    {
        $record->update([
            'result'   => $result,
            'response' => $response,
        ]);

        // 支払い約束の場合は次回フォロー日を設定
        if ($result === 'promised') {
            $record->update(['next_action_date' => today()->addDays(3)]);
        }
    }

    /**
     * 期日超過リストの取得（ダッシュボード用）
     */
    public function getOverdueList(int $minDays = 0): \Illuminate\Support\Collection
    {
        return Invoice::with(['customer', 'dunningRecords' => fn($q) => $q->latest()])
            ->where('collection_status', 'in', ['overdue', 'dunning'])
            ->where('receivable_balance', '>', 0)
            ->where('overdue_days', '>=', $minDays)
            ->orderByDesc('overdue_days')
            ->get()
            ->map(fn($invoice) => [
                'id'              => $invoice->id,
                'invoice_number'  => $invoice->invoice_number,
                'customer_name'   => $invoice->customer->company_name,
                'balance'         => $invoice->receivable_balance,
                'due_date'        => $invoice->due_date,
                'overdue_days'    => $invoice->overdue_days,
                'last_dunning'    => $invoice->dunningRecords->first()?->dunning_type,
                'last_dunning_at' => $invoice->last_dunning_at,
                'status'          => $invoice->collection_status,
            ]);
    }

    /**
     * 月次回収レポートの生成
     */
    public function generateMonthlyReport(int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        $invoices = Invoice::whereBetween('due_date', [$startDate, $endDate])->get();

        return [
            'period'           => "{$year}年{$month}月",
            'total_billed'     => $invoices->sum('total_amount'),
            'total_collected'  => $invoices->sum('total_received'),
            'total_outstanding' => $invoices->sum('receivable_balance'),
            'collection_rate'  => $invoices->sum('total_amount') > 0
                ? round($invoices->sum('total_received') / $invoices->sum('total_amount') * 100, 1)
                : 0,
            'overdue_count'    => $invoices->where('collection_status', 'overdue')->count(),
            'dunning_sent'     => DunningRecord::whereBetween('dunning_date', [$startDate, $endDate])->count(),
        ];
    }
}
