<?php

namespace App\Models\Maintenance\Part;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenancePart extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];



}
