<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookkaruApiLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'invoice_id' => 'integer',
        'normalized_invoice_id' => 'integer',
        'seat_numbers' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'success' => 'boolean',
        'duplicate' => 'boolean',
        'unauthorized' => 'boolean',
    ];
}
