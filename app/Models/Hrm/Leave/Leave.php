<?php

namespace App\Models\Hrm\Leave;


use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leave extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];

    public function addedBy()
    {
        return $this->hasOne( User::class, 'id', 'added_by');
    }
    public function decision()
    {
        return $this->hasOne( User::class, 'id', 'decider_id');
    }

    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }
}
