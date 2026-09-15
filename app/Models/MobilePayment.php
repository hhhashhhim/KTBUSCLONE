<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobilePayment extends Model
{
    protected $guarded = [];
    protected $casts = [
        'expires_at' => 'datetime', 'started_at' => 'datetime',
        'checked_at' => 'datetime', 'paid_at' => 'datetime',
        'amount_minor' => 'integer', 'ticket_count' => 'integer', 'wallet_points' => 'integer',
    ];
}
