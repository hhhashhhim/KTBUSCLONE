<?php
namespace App\Models;

use App\Models\Bus\Bus;
use App\Models\Maintenance\FaultClaim;
use App\Models\Maintenance\MaintenancePart;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaultClaimPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_id',
        'fault_claim_id',
        'dock_request_id',
        'bus_id',
        'added_by',
        'company_id',
        'status',
    ];

    public function faultClaim()
    {
        return $this->belongsTo(FaultClaim::class);
    }

    public function part()
    {
        return $this->belongsTo(MaintenancePart::class);
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }
}
