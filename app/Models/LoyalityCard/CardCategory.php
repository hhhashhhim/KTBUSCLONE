<?php

namespace App\Models\LoyalityCard;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CardCategory extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];
}
