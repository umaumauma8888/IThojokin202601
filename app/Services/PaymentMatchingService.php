<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentReceipt;
use App\Models\ReceivableMatching;
use App\Models\DunningRecord;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\DunningNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 入金消込サービス
 *
 * インボイス対応類型「決済機能」対応：
 * 商品売買に伴う金銭の授受による債権債務管理業務の負担を解消させる機能
 *
 * 主な機能：
 * 1. 入金登録と売掛金への自動充当（消込処理）
 * 2. 売掛残高のリアルタイム更新
 * 3. 部分入金・過入金のハンドリング
 * 4. 督促管理との連携
 */
class PaymentMatchingService
{
    /**
     * 入金を登録し、売掛金を自動消込する
     *
     * @param array $data 入金データ
     * @return PaymentReceipt
     */
    public function recordAndMatch(array $data): PaymentReceipt
    {
        return DB::transaction(function () use ($data) {
            $invoice = Invoice::lockForUpdate()->findOrFail($data['invoice_id']);

            // 入金記録の作成
            $receipt = PaymentReceipt::create([
                'invoice_id'      => $invoice->id,
                'customer_id'     => $invoice->customer_id,
                'received_amount' => $data['received_amount'],
                'received_date'   => $data['received_date'],
                'payment_method'  => $data['payment_method'] ?? 'bank_transfer',
                'bank_name'       => $data['bank_name'] ?? null,
                'bank_account'    => $data['bank_account'] ?? null,
                'memo'            => $data['memo'] ?? null,
                'recorded_by'     => auth()->id(),
            ]);

            // 売掛金への自動充当（消込）
            $this->applyToReceivable($receipt, $invoice);

            // 通知送信
            $invoice->customer->notify(new PaymentReceivedNotification($receipt, $invoice));

            Log::info('入金消込完了', [
                'invoice_id'      => $invoice->id,
                'received_amount' => $data['received_amount'],
                'balance_after'   => $invoice->fresh()->receivable_balance,
            ]);

            return $receipt;
        });
    }

    /**
     * 売掛金への充当処理（消込の実体）
     */
    private function applyToReceivable(PaymentReceipt $receipt, Invoice $invoice): void
    {
        $remainingBalance = $invoice->receivable_balance;
        $matchAmount      = min($receipt->received_amount, $remainingBalance);

        // 消込記録の作成
        ReceivableMatching::create([
            'payment_receipt_id' => $receipt->id,
            'invoice_id'         => $invoice->id,
            'matched_amount'     => $matchAmount,
            'status'             => $this->determineMatchStatus($receipt->received_amount, $remainingBalance),
            'matched_at'         => now(),
            'matched_by'         => auth()->id(),
        ]);

        // 請求書の売掛残高を更新
        $newBalance    = max(0, $remainingBalance - $receipt->received_amount);
        $totalReceived = $invoice->total_received + $receipt->received_amount;

        $invoice->update([
            'total_received'    => $totalReceived,
            'receivable_balance' => $newBalance,
            'collection_status' => $this->determineCollectionStatus($newBalance, $invoice),
        ]);

        // 過入金の場合はアラート
        if ($receipt->received_amount > $remainingBalance) {
            $overpayment = $receipt->received_amount - $remainingBalance;
            Log::warning('過入金検知', [
                'invoice_id'  => $invoice->id,
                'overpayment' => $overpayment,
            ]);
        }
    }

    /**
     * 消込ステータスの判定
     */
    private function determineMatchStatus(int $receivedAmount, int $balance): string
    {
        if ($receivedAmount >= $balance) return 'matched';
        if ($receivedAmount > 0)        return 'partial';
        return 'unmatched';
    }

    /**
     * 回収ステータスの判定
     */
    private function determineCollectionStatus(int $balance, Invoice $invoice): string
    {
        if ($balance <= 0) return 'paid';

        // 期日超過チェック
        $dueDate = Carbon::parse($invoice->due_date);
        if ($dueDate->isPast()) {
            return $invoice->dunningRecords()->exists() ? 'dunning' : 'overdue';
        }

        if ($invoice->total_received > 0) return 'partial';
        return 'unpaid';
    }

    /**
     * 消込の取り消し
     */
    public function reverseMatching(ReceivableMatching $matching): void
    {
        DB::transaction(function () use ($matching) {
            $invoice = Invoice::lockForUpdate()->findOrFail($matching->invoice_id);

            // 残高を戻す
            $invoice->update([
                'total_received'     => $invoice->total_received - $matching->matched_amount,
                'receivable_balance' => $invoice->receivable_balance + $matching->matched_amount,
                'collection_status'  => $this->determineCollectionStatus(
                    $invoice->receivable_balance + $matching->matched_amount,
                    $invoice
                ),
            ]);

            $matching->delete();
            $matching->paymentReceipt->delete();

            Log::info('消込取消完了', ['invoice_id' => $invoice->id]);
        });
    }

    /**
     * 期日超過の未入金請求書を一括更新（日次バッチ用）
     */
    public function updateOverdueStatuses(): int
    {
        $updated = Invoice::where('collection_status', 'unpaid')
            ->where('due_date', '<', today())
            ->where('receivable_balance', '>', 0)
            ->update([
                'collection_status' => 'overdue',
                'overdue_date'      => today(),
                'overdue_days'      => DB::raw('DATEDIFF(CURDATE(), due_date)'),
            ]);

        // 既に overdue の請求書の超過日数を更新
        Invoice::where('collection_status', 'overdue')
            ->update([
                'overdue_days' => DB::raw('DATEDIFF(CURDATE(), due_date)'),
            ]);

        Log::info("期日超過ステータス更新: {$updated}件");
        return $updated;
    }

    /**
     * 売掛残高サマリーの取得
     */
    public function getReceivableSummary(): array
    {
        $summary = Invoice::selectRaw(
            'COUNT(*) as total_count,
             SUM(receivable_balance) as total_balance,
             SUM(CASE WHEN collection_status = "unpaid" THEN receivable_balance ELSE 0 END) as unpaid_balance,
             SUM(CASE WHEN collection_status = "partial" THEN receivable_balance ELSE 0 END) as partial_balance,
             SUM(CASE WHEN collection_status = "overdue" THEN receivable_balance ELSE 0 END) as overdue_balance,
             SUM(CASE WHEN collection_status = "dunning" THEN receivable_balance ELSE 0 END) as dunning_balance,
             COUNT(CASE WHEN collection_status = "overdue" THEN 1 END) as overdue_count,
             COUNT(CASE WHEN overdue_days > 90 THEN 1 END) as long_overdue_count'
        )->where('receivable_balance', '>', 0)->first();

        return [
            'total_balance'    => $summary->total_balance ?? 0,
            'unpaid_balance'   => $summary->unpaid_balance ?? 0,
            'partial_balance'  => $summary->partial_balance ?? 0,
            'overdue_balance'  => $summary->overdue_balance ?? 0,
            'dunning_balance'  => $summary->dunning_balance ?? 0,
            'overdue_count'    => $summary->overdue_count ?? 0,
            'long_overdue_count' => $summary->long_overdue_count ?? 0,
            'collection_rate'  => $this->calculateCollectionRate(),
        ];
    }

    /**
     * 回収率の計算
     */
    private function calculateCollectionRate(): float
    {
        $total    = Invoice::sum('total_amount');
        $received = Invoice::sum('total_received');
        if ($total <= 0) return 0.0;
        return round(($received / $total) * 100, 1);
    }
}
