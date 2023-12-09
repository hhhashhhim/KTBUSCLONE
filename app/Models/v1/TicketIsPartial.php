<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketIsPartial extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];


}
