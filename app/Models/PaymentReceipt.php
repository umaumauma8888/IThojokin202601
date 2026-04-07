<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// ===== PaymentReceipt（入金記録）=====
class PaymentReceipt extends Model
{
    protected $fillable = [
        'invoice_id', 'customer_id', 'received_amount', 'received_date',
        'payment_method', 'bank_name', 'bank_account', 'memo', 'recorded_by',
    ];

    protected $casts = [
        'received_date'   => 'date',
        'received_amount' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function receivableMatchings(): HasMany
    {
        return $this->hasMany(ReceivableMatching::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'bank_transfer' => '銀行振込',
            'credit_card'   => 'クレジットカード',
            'cash'          => '現金',
            default         => 'その他',
        };
    }
}
