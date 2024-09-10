<?php

namespace App\Models\Account;

use App\Models\User;
use App\Models\Terminal;
use App\Models\company\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTransaction extends Model
{
    use HasFactory, softDeletes;
    protected $guarded = [];

    public function added_by_name()
    {
        return $this->belongsTo( User::class, 'added_by', 'id');
    }

    public function other_head_name()
    {
        return $this->belongsTo( AccountHead::class , 'other_account_head_id', 'id');
    }
    
    public function terminal()
    {
        return $this->belongsTo( Terminal::class, 'terminal_id', 'id');
    }
    
    public function updated_by_name()
    {
        return $this->belongsTo( User::class, 'updated_by', 'id');
    }
    
    public function approved_by_name()
    {
        return $this->belongsTo( User::class, 'approved_by', 'id');
    }
    
    public function account_head()
    {
        return $this->belongsTo( AccountHead::class, 'account_head_id', 'id');
    }
    
    public function account_receivers()
    {
        return $this->hasMany( AccountTransaction::class, 'other_account_head_id', 'account_head_id');
    }
    
    public function level_two()
    {
        return $this->belongsTo( Account::class, 'account_id', 'id');
    }
    
    public function level_four()
    {
        return $this->belongsTo( AccountGroup::class, 'group_id', 'id');
    }
}
