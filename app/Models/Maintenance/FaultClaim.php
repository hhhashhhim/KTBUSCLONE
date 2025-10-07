<?php

namespace App\Models\Maintenance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Company;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Bus\Bus;
use App\Models\FaultClaimPart;
use App\Models\Hrm\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FaultClaim extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];

    // status = pending / dock time / resolved
    protected $casts = [
        'images' => 'array', // automatically cast JSON to array
    ];
    public function dock_requests()
    {
        return $this->hasMany(DockRequest::class);
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function driver()
    {
        return $this->belongsTo(Employee::class, 'driver_id');
    }

    public function claimParts()
    {
        return $this->hasMany(FaultClaimPart::class, 'fault_claim_id');
    }

    public function inspectionResult()
    {
        return $this->hasOne(InspectionResult::class, 'fault_claim_id');
    }
    public function parts()
    {
        return $this->hasMany(FaultClaimPart::class, 'fault_claim_id');
    }
}
