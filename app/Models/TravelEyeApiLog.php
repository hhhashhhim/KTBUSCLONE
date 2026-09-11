<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelEyeApiLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'ticket_id' => 'integer',
        'terminal_id' => 'integer',
        'request_payload' => 'array',
        'http_status' => 'integer',
        'attempts' => 'integer',
        'last_attempt_at' => 'datetime',
    ];
}
