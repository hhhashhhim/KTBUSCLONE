<?php

namespace App\Models\Bus;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusClass extends Model
{
    use HasFactory, softDeletes;

    
    protected $casts = [
        'seat_map' => 'array'
    ];
    
     protected $guarded = [];


    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function updated_by()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

}
