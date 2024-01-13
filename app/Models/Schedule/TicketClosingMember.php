<?php

namespace App\Models\Schedule;

use App\Models\Bus\Bus;
use App\Models\Bus\BusClass;
use App\Models\City;
use App\Models\Company;
use App\Models\Hrm\Employee\Employee;
use App\Models\FareClass;
use App\Models\Route\Route;
use App\Models\Surcharge\Surcharge;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketClosingMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function addedBy()
    {
        return $this->hasOne( User::class, 'id', 'added_by' );
    }
    
    public function host_name()
    {
        return $this->hasOne( User::class, 'id', 'user_id' );
    }
    
    public function driver_name()
    {
        return $this->hasOne( Employee::class, 'id', 'user_id' );
    }

}
