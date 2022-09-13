<?php

namespace App\Models\Route;

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
}
