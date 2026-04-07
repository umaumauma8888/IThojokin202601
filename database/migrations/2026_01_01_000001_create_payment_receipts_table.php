<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 入金消込テーブル
 * インボイス対応類型「決済機能」要件：
 * 商品売買に伴う金銭の授受による債権債務管理業務の負担を解消させる機能
 */
return new class extends Migration
{
    public function up(): void
    {
        // 入金記録テーブル
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers');
            $table->decimal('received_amount', 12, 0)->comment('入金額');
            $table->date('received_date')->comment('入金日');
            $table->enum('payment_method', ['bank_transfer', 'credit_card', 'cash', 'other'])
                  ->default('bank_transfer')->comment('入金方法');
            $table->string('bank_name')->nullable()->comment('振込元銀行名');
            $table->string('bank_account')->nullable()->comment('振込元口座');
            $table->text('memo')->nullable()->comment('備考');
            $table->foreignId('recorded_by')->constrained('users')->comment('入力担当者');
            $table->timestamps();

            $table->index(['invoice_id', 'received_date']);
            $table->index('customer_id');
        });

        // 売掛消込テーブル（入金と請求書の突合記録）
        Schema::create('receivable_matchings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_receipt_id')->constrained('payment_receipts')->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->decimal('matched_amount', 12, 0)->comment('充当金額');
            $table->enum('status', ['matched', 'partial', 'unmatched'])->default('matched');
            $table->timestamp('matched_at')->comment('消込実施日時');
            $table->foreignId('matched_by')->constrained('users')->comment('消込担当者');
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });

        // 督促管理テーブル
        Schema::create('dunning_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers');
            $table->enum('dunning_type', ['first', 'second', 'final', 'legal'])
                  ->comment('督促種別：初回/二次/最終/法的措置');
            $table->enum('method', ['email', 'phone', 'mail', 'visit'])->comment('督促方法');
            $table->date('dunning_date')->comment('督促実施日');
            $table->date('next_action_date')->nullable()->comment('次回対応予定日');
            $table->text('content')->comment('督促内容');
            $table->text('response')->nullable()->comment('相手先の回答');
            $table->enum('result', ['pending', 'promised', 'paid', 'disputed', 'uncollectible'])
                  ->default('pending')->comment('結果');
            $table->foreignId('handled_by')->constrained('users')->comment('担当者');
            $table->timestamps();

            $table->index(['invoice_id', 'dunning_type']);
            $table->index(['customer_id', 'result']);
        });

        // 督促スケジュール自動設定テーブル
        Schema::create('dunning_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('スケジュール名');
            $table->integer('days_after_due')->comment('期日超過後の日数');
            $table->enum('dunning_type', ['first', 'second', 'final']);
            $table->enum('method', ['email', 'phone', 'mail']);
            $table->string('email_template')->nullable()->comment('メールテンプレート名');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dunning_schedules');
        Schema::dropIfExists('dunning_records');
        Schema::dropIfExists('receivable_matchings');
        Schema::dropIfExists('payment_receipts');
    }
};
