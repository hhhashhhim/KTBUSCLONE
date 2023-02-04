<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TerminalDiscount extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];
    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function updated_by()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }
    
    public function terminal(){
        return $this->hasOne( Terminal::class,'id','terminal_id' );
    }

}
