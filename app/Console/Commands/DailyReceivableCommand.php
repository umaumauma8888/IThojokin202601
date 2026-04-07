<?php

namespace App\Console\Commands;

use App\Services\PaymentMatchingService;
use App\Services\DunningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 債権管理日次バッチ
 *
 * Scheduler設定例 (app/Console/Kernel.php):
 *   $schedule->command('receivable:daily')->dailyAt('07:00');
 *
 * 手動実行:
 *   php artisan receivable:daily
 *   php artisan receivable:daily --dry-run  （確認のみ）
 */
class DailyReceivableCommand extends Command
{
    protected $signature   = 'receivable:daily {--dry-run : 実際には実行せず確認のみ}';
    protected $description = '期日超過ステータスの更新と自動督促メールの送信（日次）';

    public function __construct(
        private readonly PaymentMatchingService $matchingService,
        private readonly DunningService         $dunningService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('=== 債権管理日次バッチ 開始 ===');
        $this->info('実行日: ' . now()->format('Y-m-d H:i:s'));

        if ($isDryRun) {
            $this->warn('【DRY-RUNモード】実際には変更を行いません。');
        }

        // Step 1: 期日超過ステータスの更新
        $this->info('');
        $this->info('[1/2] 期日超過ステータス更新中...');

        if (!$isDryRun) {
            $updated = $this->matchingService->updateOverdueStatuses();
            $this->info("  → {$updated}件の請求書を期日超過に更新しました。");
        } else {
            $this->line('  → DRY-RUN: スキップ');
        }

        // Step 2: 自動督促メール送信
        $this->info('');
        $this->info('[2/2] 自動督促メール送信中...');

        if (!$isDryRun) {
            $results = $this->dunningService->processAutoDunning();
            $this->info("  → 送信: {$results['sent']}件 / スキップ: {$results['skipped']}件 / エラー: {$results['errors']}件");

            if ($results['errors'] > 0) {
                $this->warn('  ⚠️ 一部エラーが発生しました。ログを確認してください。');
            }
        } else {
            $this->line('  → DRY-RUN: スキップ');
        }

        $this->info('');
        $this->info('=== 債権管理日次バッチ 完了 ===');

        Log::info('債権管理日次バッチ完了', ['dry_run' => $isDryRun]);

        return Command::SUCCESS;
    }
}
