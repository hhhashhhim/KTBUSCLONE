<?php

namespace App\Models\Bus;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusSeatMap extends Model
{
    use HasFactory, SoftDeletes;
    // protected $casts = [
    //     'seat_map' => 'array'
    // ];

    protected $guarded = [];

    protected $fillable = [
        'bus_id',
        'row_index',
        'col_index',
        'no_of_rows',
        'no_of_cols',
        'status',
        'seat_map',
        'company_id',
        'added_by',
        'updated_by',
        'row_index',
        'row_index',
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
}
