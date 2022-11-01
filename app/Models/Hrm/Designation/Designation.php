<?php

namespace App\Models\Hrm\Designation;

use App\Models\Company;
use App\Models\Hrm\Department\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    public function addedBy()
    {
        return $this->hasOne( User::class, 'id', 'added_by');
    }

    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }

    public function department(){
        return $this->hasOne( Department::class,'id','department_id' );
    }

}
