<?php

namespace App\Models;

use App\Models\Hrm\Department\Department;
use App\Models\Hrm\Employee\Employee;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Terminal extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function city()
    {
        return $this->hasOne(City::class, 'id', 'city_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function updated_by()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function departments()
    {
        return $this->hasMany(Department::class, 'terminal_id', 'id');
    }
    public function employees()
    {
        return $this->hasMany(Employee::class, 'terminal_id', 'id');
    }
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'terminal_id', 'id');
    }

}
