<?php

namespace App\Models\Maintenance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Company;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Bus\Bus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DockRequest extends Model
{
    use HasFactory, softDeletes;

    // status = apprvoed / pending
    
    protected $guarded = [];

    public function faultClaim()
    {
        return $this->belongsTo(FaultClaim::class);
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }
    
    public function approved()
    {
        return $this->belongsTo(User::class,"approved_by", "id");
    }

}
