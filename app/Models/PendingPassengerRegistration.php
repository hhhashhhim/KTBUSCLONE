<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class PendingPassengerRegistration extends Authenticatable
{
    use HasApiTokens;

    protected $guarded = [];
    protected $hidden = ['password', 'cnic', 'otp_digest'];
    protected $casts = ['expires_at' => 'datetime', 'otp_expires_at' => 'datetime'];

    public function passengerSummary(): array
    {
        // Only submitted details. Never load a customer before proof of ownership.
        return ['id' => $this->id, 'full_name' => $this->full_name, 'mobile' => $this->mobile,
            'email' => $this->email, 'cnic' => null, 'gender' => null, 'mobile_verified' => false];
    }
}
