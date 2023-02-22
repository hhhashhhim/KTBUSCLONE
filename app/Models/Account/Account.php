<?php

namespace App\Models\Account;

use App\Models\FareClass;
use App\Models\User;
use App\Models\Maintenance\MaintenancePartLink;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, softDeletes;
    protected $guarded = [];


}
