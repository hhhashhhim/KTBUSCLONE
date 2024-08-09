<?php

namespace App\Models\Account;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountHead extends Model
{
    use HasFactory, softDeletes;
    protected $guarded = [];

    public function level_one(){
        return $this->belongsTo( Account::class, 'parent_account_id', 'id');
    }

    public function level_two(){
        return $this->belongsTo( Account::class, 'account_id', 'id');
    }
    
    public function level_three(){
        return $this->belongsTo( AccountGroup::class, 'parent_group_id', 'id');
    }
    
    public function level_four(){
        return $this->belongsTo( AccountGroup::class, 'group_id', 'id');
    }
    
    public function head_bank(){
        return $this->hasOne( Bank::class, 'account_head_id', 'id');
    }
    
    public function head_cash(){
        return $this->hasOne( Cash::class, 'account_head_id', 'id');
    }
}
