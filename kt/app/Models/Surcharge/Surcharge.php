<?php

namespace App\Models\Surcharge;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Surcharge extends Model
{
    use HasFactory, SoftDeletes ;
    protected $guarded = [];

}
