<?php

/**
 * app/Console/Kernel.php の protected function schedule() メソッドに
 * 以下を追加してください。
 *
 * 【追加場所】
 * protected function schedule(Schedule $schedule): void
 * {
 *     // ↓ ここから追加
 *     $schedule->command('receivable:daily')
 *              ->dailyAt('07:00')
 *              ->withoutOverlapping()
 *              ->runInBackground()
 *              ->onFailure(function () {
 *                  \Log::error('債権管理日次バッチが失敗しました');
 *              });
 *     // ↑ ここまで追加
 * }
 *
 * 【コマンド登録】
 * protected $commands = [
 *     \App\Console\Commands\DailyReceivableCommand::class, // ← 追加
 * ];
 *
 * 【cron設定（サーバー側）】
 * サーバーの crontab に以下を追加してください：
 * * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
 */

// このファイルは説明用のコメントファイルです。
// 実際の Kernel.php を直接編集してください。
