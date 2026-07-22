<?php

namespace App\Models;

use App\Models\Booking\TicketELT;
use App\Models\Booking\TicketsOverIssue;
use App\Models\Bus\BusClass;
use App\Models\Bus\Bus;
use App\Models\Booking\BookingCancel;
use App\Models\Booking\TicketReschedule;
use App\Models\Schedule\Schedule;
use App\Models\Route\Route;
use App\Models\Schedule\ScheduleDetail;
use App\Models\TerminalCommission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, softDeletes;

    protected $fillable = [
        'id', // Primary key of the ticket.
        'company_id', // Company that owns the ticket.
        'terminal_id', // Terminal where the ticket was issued.
        'ticket_closing_id', // Related ticket-closing record.
        'bus_id', // Bus assigned to the ticket.
        'departure_city_id', // Passenger's departure city.
        'destination_city_id', // Passenger's destination city.
        'bus_class_id', // Bus or fare class selected for the ticket.
        'booking_no', // Booking reference number.
        'invoice_id', // Invoice reference shared by booking tickets.
        'transaction_id', // Payment transaction reference.
        'refund_reason', // Reason the ticket was refunded.
        'refund_amount', // Amount refunded to the passenger.
        'refund_percentage', // Percentage of the fare refunded.
        'schedule_date', // Travel date of the assigned schedule.
        'schedule_time', // Display time of the assigned schedule.
        'schedule_time_exact', // Exact schedule time used for sorting and filtering.
        'date', // Business or booking date of the ticket.
        'seat_no', // Seat number assigned to the passenger.
        'seat_fare', // Fare charged for the seat.
        'customer_id', // Customer or passenger linked to the ticket.
        'schedule_id', // Schedule linked to the ticket.
        'route_id', // Travel route linked to the ticket.
        'schedule_details_id', // Specific schedule-detail record.
        'remarks', // Additional notes about the ticket.
        'gender', // Passenger gender used for seat allocation.
        'ticket_merge_id', // Ticket-closing merge reference.
        'is_partial', // Whether the ticket has a partial payment.
        'terminal_name', // Name of the issuing terminal stored on the ticket.
        'type', // Ticket status or booking type.
        'online_terminal', // Online terminal associated with the booking.
        'reschedule_type', // Type or source of ticket rescheduling.
        'discount', // Direct discount applied to the seat fare.
        'terminal_discount', // Discount configured by the terminal.
        'schedule_discount', // Discount configured for the schedule.
        'points_usage', // Loyalty points used for the booking.
        'discount_type', // Category or method of discount applied.
        'booked_time', // Time at which the booking was completed.
        'time', // General ticket transaction time.
        'deleted_at', // Soft-deletion timestamp.
        'added_by', // User who created the ticket.
        'created_at', // Date and time the ticket was created.
        'updated_by', // User who last updated the ticket.
        'updated_at', // Date and time the ticket was last updated.
    ];

    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function added_name()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function route()
    {
        return $this->hasOne(Route::class, 'id', 'route_id');
    }

    public function departure_city()
    {
        return $this->hasOne(City::class, 'id', 'departure_city_id');
    }

    public function destination_city()
    {
        return $this->hasOne(City::class, 'id', 'destination_city_id');
    }

    public function updated_by()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function updated_name()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function seatClass()
    {
        return $this->hasOne(FareClass::class, 'id', 'bus_class_id');
    }

    public function busClass()
    {
        return $this->hasOne(BusClass::class, 'id', 'bus_class_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }

    public function schedule()
    {
        return $this->hasOne(Schedule::class, 'id', 'schedule_id');
    }

    public function scheduleDetail()
    {
        return $this->hasOne(ScheduleDetail::class, 'id', 'schedule_details_id');
    }

    public function terminal()
    {
        return $this->hasOne(Terminal::class, 'id', 'terminal_id');
    }

    public function ticketElt()
    {
        return $this->hasOne(TicketELT::class, 'ticket_id', 'id');
    }

    public function elt()
    {
        return $this->hasOne(TicketELT::class, 'ticket_id', 'id');
    }

    public function commission()
    {
        return $this->hasOne(TerminalCommission::class,"terminal_id","terminal_id");
    }

    public function bus()
    {
        return $this->hasOne(Bus::class,"id","bus_id");
    }

    public function cancel_ticket()
    {
        return $this->hasOne(BookingCancel::class, 'ticket_id', 'id');
    }

    public function overIssueSeats()
    {
        return $this->hasOne(TicketsOverIssue::class, 'ticket_id', 'id');
    }

    public function reschedule_seat()
    {
        return $this->hasOne(TicketReschedule::class, 'old_ticket_id', 'id');
    }
}
