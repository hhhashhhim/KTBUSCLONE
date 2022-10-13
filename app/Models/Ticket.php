<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'bus_id',
        'seatNo',
        'customer_id',
        'schedule_id',
        'remarks',
        'for_female',
        'type',
        'discount',
    ];
}
