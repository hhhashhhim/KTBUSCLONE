<?php

namespace App\Models;

use App\Models\Booking\TicketELT;
use App\Models\Booking\TicketsOverIssue;
use App\Models\Bus\BusClass;
use App\Models\Bus\Bus;
use App\Models\Booking\BookingCancel;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\TerminalCommission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];

}
