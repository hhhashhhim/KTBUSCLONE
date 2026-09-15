<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class PassengerAccount extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_digest',
        'password_reset_otp_digest',
        'password_reset_token_digest',
    ];

    protected $casts = [
        'mobile_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'password_reset_otp_expires_at' => 'datetime',
        'password_reset_token_expires_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function tickets()
    {
        return $this->hasManyThrough(
            Ticket::class,
            Customer::class,
            'id',
            'customer_id',
            'customer_id',
            'id'
        );
    }

    public function savedPassengers()
    {
        return $this->hasMany(SavedPassenger::class);
    }
}
