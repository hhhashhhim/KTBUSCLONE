<?php

namespace App\Models\Route;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Route extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function fares(){
        return $this->hasMany(RouteFare::class, 'route_id', 'id');
    }
    public function terminals(){
        return $this->hasMany(RouteTerminal::class, 'route_id', 'id');
    }
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
}
