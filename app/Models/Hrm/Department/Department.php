<?php

namespace App\Models\Hrm\Department;

use App\Models\Company;
use App\Models\Hrm\Designation\Designation;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded =[];


    public function addedBy()
    {
        return $this->hasOne( User::class, 'id', 'added_by');
    }

    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }


    public function designation(){
        return $this->hasMany(Designation::class, 'department_id', 'id');
    }


    public function terminal(){

        return $this->hasOne(Terminal::class, 'id', 'terminal_id');
    }


}
