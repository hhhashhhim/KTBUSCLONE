<?php

namespace App\Models\Bus;

use App\Models\FareClass;
use App\Models\User;
use App\Models\Maintenance\MaintenancePartLink;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bus extends Model
{
    use HasFactory, softDeletes;
    protected $guarded = [];

    public function busClass()
    {
        return $this->hasOne(BusClass::class, 'id', 'fare_class_id');
    }
    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }
    public function updated_by()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }
    public function maintenancePartLink()
    {
        return $this->hasMany(maintenancePartLink::class, 'bus_id', 'id');
    }

}
