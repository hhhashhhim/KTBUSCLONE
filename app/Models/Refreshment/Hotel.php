<?php

namespace App\Models\Refreshment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Company;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Bus\Bus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hotel extends Model
{
    use HasFactory, softDeletes;
    
    protected $guarded = [];

    public function user()
    {
        return $this->hasOne( User::class, 'id', 'user_id');
    }
    
    // public function partName()
    // {
    //     return $this->hasOne( MaintenancePart::class, 'id', 'part_id');
    // }

}
