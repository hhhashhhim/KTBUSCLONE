<?php

namespace App\Models\Booking;

use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingCancel extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }

    public function added_by_name()
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
