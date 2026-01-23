<?php

namespace App\Models;

use App\Models\Account\Bank;
use App\Models\Bus\Bus;
use App\Models\Schedule\TicketClosing;
use App\Models\Schedule\TicketClosingMerge;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketClosingShortage extends Model
{
    use HasFactory;

    protected $table = 'ticket_closing_shortages';

    protected $fillable = [
        'terminal_id',
        'bus_id',
        'passenger_count',
        'kt_commission',
        'elt',
        'cancellation_amount',
        'route_id',
        'total_receivable', // Before Other Commission
        'other_commission',
        'total_received_cash',
        'bank_id',
        'total_received_bank',
        'shortage',
        'received',
        'ticket_closing_id', // this is Merge ID
        'company_id',
        'type',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships (Optional but recommended)
    |--------------------------------------------------------------------------
    */

    public function terminal()
    {
        return $this->belongsTo(Terminal::class);
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function ticketClosing()
    {
        return $this->belongsTo(TicketClosing::class);
    }

    public function ticket_closing_merge()
    {
        return $this->belongsTo(TicketClosingMerge::class, 'ticket_closing_id');
    }
}
