<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DunningRecord extends Model
{
    protected $fillable = [
        'invoice_id', 'customer_id', 'dunning_type', 'method', 'dunning_date',
        'next_action_date', 'content', 'response', 'result', 'handled_by',
    ];

    protected $casts = [
        'dunning_date'     => 'date',
        'next_action_date' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getDunningTypeLabelAttribute(): string
    {
        return match($this->dunning_type) {
            'first'  => '初回督促',
            'second' => '二次督促',
            'final'  => '最終督促',
            'legal'  => '法的措置',
            default  => '-',
        };
    }

    public function getResultLabelAttribute(): string
    {
        return match($this->result) {
            'pending'       => '対応中',
            'promised'      => '支払約束あり',
            'paid'          => '入金確認済',
            'disputed'      => '支払拒否',
            'uncollectible' => '回収不能',
            default         => '-',
        };
    }
}
