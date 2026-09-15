<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileAppConfig extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'force_update' => 'boolean',
        'maintenance_mode' => 'boolean',
        'payment_methods' => 'array',
        'features' => 'array',
    ];
}
