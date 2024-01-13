<?php

namespace App\Models\Account;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountCategory extends Model
{
    use HasFactory, softDeletes;
    protected $guarded = [];

    public function firstLevel()
    {
        return $this->hasOne(Account::class, 'id', 'first_level_id');
    }
    
    public function secondLevel()
    {
        return $this->hasOne(Account::class, 'id', 'second_level_id');
    }
}
