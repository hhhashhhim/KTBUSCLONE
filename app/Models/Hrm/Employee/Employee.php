<?php

namespace App\Models\Hrm\Employee;

use App\Models\Company;
use App\Models\Hrm\Department\Department;
use App\Models\Hrm\Designation\Designation;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];


    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function department()
    {
        return $this->hasOne(Department::class, 'id', 'department_id');
    }

    public function designation()
    {
        return $this->hasOne(Designation::class, 'id', 'designation_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function terminal()
    {
        return $this->hasOne(Terminal::class, 'id', 'terminal_id');
    }
}
