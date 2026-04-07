# CLAUDE SUITE - 入金消込・督促管理機能 セットアップガイド

## 概要

本機能は「デジタル化・AI導入補助金2026」インボイス対応類型における  
**「決済機能」（共P-02：商品売買に伴う金銭の授受による債権債務管理業務の負担を解消させる機能）**  
の登録要件を満たすために実装しました。

---

## 追加ファイル一覧

```
database/
  migrations/
    2026_01_01_000001_create_payment_receipts_table.php  # 入金・消込・督促テーブル
    2026_01_01_000002_add_receivable_columns_to_invoices.php  # invoicesテーブル拡張

  seeders/
    DunningScheduleSeeder.php  # 督促スケジュール初期データ

app/
  Services/
    PaymentMatchingService.php  # 入金消込ビジネスロジック
    DunningService.php          # 督促管理ビジネスロジック

  Http/
    Controllers/
      PaymentReceiptController.php  # 入金消込コントローラー
      DunningController.php         # 督促管理コントローラー
    Requests/
      PaymentReceiptRequest.php     # 入金バリデーション
      DunningRequest.php            # 督促バリデーション

  Models/
    PaymentReceipt.php          # 入金記録モデル
    ReceivableMatching.php      # 消込記録モデル
    DunningRecord.php           # 督促記録モデル
    DunningSchedule.php         # 督促スケジュールモデル
    InvoiceReceivableTrait.php  # Invoice拡張Trait

  Console/
    Commands/
      DailyReceivableCommand.php  # 日次バッチコマンド

  Notifications/
    DunningNotification.php     # 督促メール通知

resources/views/payments/
  index.blade.php              # 入金消込ダッシュボード
  dunning/
    index.blade.php            # 督促管理一覧
    schedules.blade.php        # 督促スケジュール設定

routes/
  payment_routes.php           # ルート定義
```

---

## セットアップ手順

### 1. ファイルをリポジトリに配置

```bash
git clone https://github.com/umaumauma8888/IThojokin202601.git
cd IThojokin202601
# 上記ファイルを各ディレクトリに配置
```

### 2. マイグレーション実行

```bash
php artisan migrate
```

### 3. 初期データの投入（督促スケジュール）

```bash
php artisan db:seed --class=DunningScheduleSeeder
```

### 4. routes/web.php に追加

```php
// routes/web.php の末尾に追加
require __DIR__.'/payment_routes.php';
```

### 5. Invoice モデルに Trait を追加

```php
// app/Models/Invoice.php
use App\Models\InvoiceReceivableTrait;

class Invoice extends Model
{
    use InvoiceReceivableTrait; // ← 追加

    // 既存のコード...

    // $fillable に以下を追加
    protected $fillable = [
        // 既存のカラム...,
        'receivable_balance',
        'total_received',
        'collection_status',
        'overdue_date',
        'overdue_days',
        'last_dunning_at',
    ];
}
```

### 6. Kernel.php にスケジューラーとコマンドを登録

```php
// app/Console/Kernel.php

protected $commands = [
    \App\Console\Commands\DailyReceivableCommand::class,
];

protected function schedule(Schedule $schedule): void
{
    $schedule->command('receivable:daily')
             ->dailyAt('07:00')
             ->withoutOverlapping()
             ->runInBackground();
}
```

### 7. サーバー側 cron の設定

```bash
# crontab -e で以下を追加
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### 8. 既存の請求書データの売掛残高を初期化

```bash
# 既存データの receivable_balance を total_amount で初期化（未入金前提）
php artisan tinker
>>> App\Models\Invoice::whereNull('receivable_balance')->update(['receivable_balance' => \DB::raw('total_amount'), 'total_received' => 0]);
```

---

## 動作確認

```bash
# 日次バッチのドライラン（実際には実行しない）
php artisan receivable:daily --dry-run

# 実際に実行
php artisan receivable:daily
```

ブラウザで `/payments` にアクセスして入金消込ダッシュボードを確認してください。

---

## 補助金登録申請における根拠

本機能は以下の要件を満たします：

**登録要領P.11(ウ)「決済機能」の定義②：**
> 「共P-02に含まれる商品売買に伴う金銭の授受による **債権債務管理業務の負担を解消** させる機能」

| 要件 | 対応機能 |
|------|---------|
| 入金消込の自動化 | `PaymentMatchingService::recordAndMatch()` |
| 売掛残高リアルタイム管理 | `invoices.receivable_balance` 自動更新 |
| 入金予定日管理 | 請求書の `due_date` × 入金ステータス管理 |
| 支払督促・リマインド | `DunningService::processAutoDunning()` |
| 督促履歴管理 | `dunning_records` テーブル |
| 回収率の可視化 | `PaymentMatchingService::getReceivableSummary()` |

---

## コミット・プッシュ

```bash
git add .
git commit -m "feat: 入金消込・督促管理機能を追加

- 入金登録と売掛金自動消込（PaymentMatchingService）
- 期日超過の自動検知・ステータス更新
- 督促メール自動送信（日次バッチ: receivable:daily）
- 督促履歴・結果管理
- 入金消込ダッシュボード・督促管理画面

インボイス対応類型「決済機能」(登録要領P.11(ウ))対応"

git push origin main
```
