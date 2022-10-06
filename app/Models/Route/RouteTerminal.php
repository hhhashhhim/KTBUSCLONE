<?php

namespace App\Models\Route;

use App\Models\Terminal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class RouteTerminal extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'routes_terminals';

    protected $guarded = [];

    public function city(){
        return $this->hasOne( Terminal::class,'id','terminal_id' );
    }

}
