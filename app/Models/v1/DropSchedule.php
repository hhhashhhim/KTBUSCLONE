<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\v1\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class DropSchedule extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class, "schedule_id","id");
    }

    public function added_by()
    {
        return $this->hasOne( User::class, 'id', 'added_by' );
    }
}
