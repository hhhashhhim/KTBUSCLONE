<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RescheduleExtraAmount extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];
}
