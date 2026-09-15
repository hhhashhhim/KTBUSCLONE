<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedPassenger extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function passengerAccount()
    {
        return $this->belongsTo(PassengerAccount::class);
    }
}
