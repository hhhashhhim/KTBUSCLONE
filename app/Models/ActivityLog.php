<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    public function activity()
    {
        return $this->hasOne(User::class, 'id', 'activity_by');
    }

    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }

}
