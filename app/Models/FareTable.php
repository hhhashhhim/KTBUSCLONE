<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FareTable extends Model
{
    use HasFactory;
    protected $fillable = [
        'fare',
        'fare_class',
        'from_city_id',
        'to_city_id',
        'company_id',
        'commission_flat',
        'commission_percentage',
        'terminal_commission',
        'time_difference',
        'surcharge',
        'surcharge_start_date',
        'surcharge_end_date',
        'advance_availability',
        'is_active',
        'added_by',
    ];
}
