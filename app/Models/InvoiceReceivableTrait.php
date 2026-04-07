<?php

/**
 * 既存の app/Models/Invoice.php に以下のリレーションとスコープを追加してください。
 *
 * use App\Models\PaymentReceipt;
 * use App\Models\ReceivableMatching;
 * use App\Models\DunningRecord;
 */

namespace App\Models;

// === 既存のInvoiceモデルに追加するメソッド群 ===
// 実際の Invoice.php にコピー&ペーストしてください

trait InvoiceReceivableTrait
{
    // ===== リレーション =====

    public function paymentReceipts()
    {
        return $this->hasMany(PaymentReceipt::class);
    }

    public function receivableMatchings()
    {
        return $this->hasMany(ReceivableMatching::class);
    }

    public function dunningRecords()
    {
        return $this->hasMany(DunningRecord::class)->orderByDesc('dunning_date');
    }

    // ===== スコープ =====

    /** 未収入金のみ（売掛残高あり） */
    public function scopeOutstanding($query)
    {
        return $query->where('receivable_balance', '>', 0);
    }

    /** 期日超過のみ */
    public function scopeOverdue($query)
    {
        return $query->whereIn('collection_status', ['overdue', 'dunning']);
    }

    /** 督促が必要（指定日数超過） */
    public function scopeNeedsDunning($query, int $minDays = 7)
    {
        return $query->where('collection_status', 'overdue')
                     ->where('overdue_days', '>=', $minDays);
    }

    // ===== アクセサ =====

    /** 回収ステータスの日本語ラベル */
    public function getCollectionStatusLabelAttribute(): string
    {
        return match($this->collection_status) {
            'unpaid'        => '未入金',
            'partial'       => '一部入金',
            'paid'          => '入金済',
            'overdue'       => '期日超過',
            'dunning'       => '督促中',
            'uncollectible' => '回収不能',
            default         => '-',
        };
    }

    /** 全額消込済みか */
    public function getIsFullyPaidAttribute(): bool
    {
        return $this->receivable_balance <= 0;
    }

    /** 入金進捗率（%） */
    public function getPaymentProgressAttribute(): float
    {
        if ($this->total_amount <= 0) return 0;
        return round(($this->total_received / $this->total_amount) * 100, 1);
    }
}
