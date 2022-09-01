<?php

namespace App\Models\Route;

use App\Models\FareTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteFare extends Model
{
    use HasFactory;
    protected $table = 'routes_fares';

    protected $fillable = ['route_id', 'fare_id','company_id','added_by'];

    public function fare_details(){
        return $this->belongsTo(FareTable::class, 'fare_id',  'id');
    }
}
