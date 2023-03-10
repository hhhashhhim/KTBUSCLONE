<?php

namespace App\Models\LoyaltyCard;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CardAssign extends Model
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

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }

    public function cardCategory()
    {
        return $this->hasOne(CardCategory::class, 'id', 'card_category_id');
    }

}
