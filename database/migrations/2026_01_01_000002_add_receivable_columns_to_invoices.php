<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * invoicesテーブルに入金消込管理カラムを追加
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('receivable_balance', 12, 0)->default(0)
                  ->comment('売掛残高（請求額 - 入金済合計）')->after('total_amount');
            $table->decimal('total_received', 12, 0)->default(0)
                  ->comment('入金済合計額')->after('receivable_balance');
            $table->enum('collection_status', [
                'unpaid',       // 未入金
                'partial',      // 一部入金
                'paid',         // 入金済（全額消込）
                'overdue',      // 期日超過
                'dunning',      // 督促中
                'uncollectible' // 回収不能
            ])->default('unpaid')->comment('回収ステータス')->after('total_received');
            $table->date('overdue_date')->nullable()->comment('期日超過確定日')->after('collection_status');
            $table->integer('overdue_days')->default(0)->comment('超過日数')->after('overdue_date');
            $table->timestamp('last_dunning_at')->nullable()->comment('最終督促日時')->after('overdue_days');

            $table->index('collection_status');
            $table->index('overdue_date');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'receivable_balance',
                'total_received',
                'collection_status',
                'overdue_date',
                'overdue_days',
                'last_dunning_at',
            ]);
        });
    }
};
