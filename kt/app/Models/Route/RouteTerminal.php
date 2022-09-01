<?php

namespace App\Models\Route;

use App\Models\Terminal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteTerminal extends Model
{
    use HasFactory;
    protected $table = 'routes_terminals';

    protected $fillable = ['route_id', 'terminal_id','company_id','added_by'];

    public function city(){
        return $this->hasOne( Terminal::class,'id','terminal_id' );
    }

}
