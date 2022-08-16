<?php

namespace App\Models\admin;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'company_id',
        'permissions',
        'added_by',
    ];
    protected $casts = [
        'permissions' => 'array'
    ];
    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }
}
