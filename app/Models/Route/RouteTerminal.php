<?php

namespace App\Models\Route;

use App\Models\Company;
use App\Models\Terminal;
use App\Models\User;
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
