<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    protected $guarded = [];
    // protected $fillable = [
    //     'company_id',
    //     'bus_id',
    //     'seat_no',
    //     'customer_id',
    //     'schedule_id',
    //     'remarks',
    //     'for_female',
    //     'type',
    //     'discount',
    // ];
}
