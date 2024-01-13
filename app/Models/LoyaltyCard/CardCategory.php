<?php

namespace App\Models\LoyaltyCard;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CardCategory extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }
    public function updatedBy()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }
}
