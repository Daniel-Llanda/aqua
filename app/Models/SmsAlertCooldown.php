<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsAlertCooldown extends Model
{
    protected $fillable = [
        'user_id',
        'pond_id',
        'condition_key',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'last_sent_at' => 'datetime',
        ];
    }
}
