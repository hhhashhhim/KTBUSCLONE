<?php

namespace App\Models\Maintenance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Company;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Bus\Bus;
use App\Models\Hrm\Employee\Employee;
use App\Models\Inventory\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InspectionResult extends Model
{
    use HasFactory, softDeletes;
    
    protected $guarded = [];

    // status = no fault / resolved
    // repair_type = in house / outsource / hybrid

    public function parts()
    {
        return $this->hasMany(InspectionResultPart::class);
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function driver()
    {
        return $this->belongsTo(Employee::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function dockRequest()
    {
        return $this->hasOne(DockRequest::class, 'fault_claim_id', 'fault_claim_id')
            ->latestOfMany(); // gets latest dock request per claim
    }
}
