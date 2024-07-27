<?php

namespace App\Models\Account;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountGroup extends Model
{
    use HasFactory, softDeletes;
    protected $guarded = [];

    public function level_two(){
        return $this->belongsTo( Account::class, 'account_id', 'id');
    }

    public function level_three(){
        return $this->belongsTo( AccountGroup::class, 'parent_id', 'id');
    }
}
