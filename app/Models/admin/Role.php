<?php

namespace App\Models\admin;

use App\Models\Company;
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
}
