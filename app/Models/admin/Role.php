<?php

namespace App\Models\admin;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Role extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];
    protected $casts = [
        'permissions' => 'array'
    ];
    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }
    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function updated_by()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }
}
