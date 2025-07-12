<?php

namespace App\Models\Maintenance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Company;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Bus\Bus;
use App\Models\Hrm\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InspectionResultPart extends Model
{
    use HasFactory, softDeletes;
    
    protected $guarded = [];

    public function part()
    {
        return $this->belongsTo(MaintenancePart::class, 'part_id');
    }
}
