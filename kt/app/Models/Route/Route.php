<?php

namespace App\Models\Route;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'company_id','added_by'];

    public function fares(){
        return $this->hasMany(RouteFare::class, 'route_id', 'id');
    }
    public function terminals(){
        return $this->hasMany(RouteTerminal::class, 'route_id', 'id');
    }
}
