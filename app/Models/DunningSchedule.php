<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DunningSchedule extends Model
{
    protected $fillable = [
        'name', 'days_after_due', 'dunning_type', 'method', 'email_template', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getDunningTypeLabelAttribute(): string
    {
        return match($this->dunning_type) {
            'first'  => '初回督促',
            'second' => '二次督促',
            'final'  => '最終督促',
            default  => '-',
        };
    }

    public function getMethodLabelAttribute(): string
    {
        return match($this->method) {
            'email' => 'メール',
            'phone' => '電話',
            'mail'  => '郵送',
            default => '-',
        };
    }

    /**
     * デフォルトスケジュールのシード
     * php artisan db:seed --class=DunningScheduleSeeder で実行
     */
    public static function defaults(): array
    {
        return [
            ['name' => '初回督促（7日超過）',  'days_after_due' => 7,  'dunning_type' => 'first',  'method' => 'email', 'is_active' => true],
            ['name' => '二次督促（30日超過）', 'days_after_due' => 30, 'dunning_type' => 'second', 'method' => 'email', 'is_active' => true],
            ['name' => '最終督促（60日超過）', 'days_after_due' => 60, 'dunning_type' => 'final',  'method' => 'email', 'is_active' => true],
        ];
    }
}
