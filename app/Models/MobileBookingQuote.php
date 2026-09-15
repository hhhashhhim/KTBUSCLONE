<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileBookingQuote extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'base_fare' => 'decimal:2',
        'discount' => 'decimal:2',
        'taxes' => 'decimal:2',
        'fees' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function passengerAccount()
    {
        return $this->belongsTo(PassengerAccount::class);
    }
}
