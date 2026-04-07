<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivableMatching extends Model
{
    protected $fillable = [
        'payment_receipt_id', 'invoice_id', 'matched_amount', 'status', 'matched_at', 'matched_by',
    ];

    protected $casts = [
        'matched_at'     => 'datetime',
        'matched_amount' => 'integer',
    ];

    public function paymentReceipt(): BelongsTo
    {
        return $this->belongsTo(PaymentReceipt::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function matcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'matched'   => '全額消込',
            'partial'   => '一部消込',
            'unmatched' => '未消込',
            default     => '-',
        };
    }
}
