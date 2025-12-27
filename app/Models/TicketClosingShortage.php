<?php

namespace App\Models;

use App\Models\account\Bank;
use App\Models\Schedule\TicketClosing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketClosingShortage extends Model
{
    use HasFactory;

    protected $table = 'ticket_closing_shortages';

    protected $fillable = [
        'terminal_id',
        'passenger_count',
        'kt_commission',
        'other_commission',
        'total_receivable',
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

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function ticketClosing()
    {
        return $this->belongsTo(TicketClosing::class);
    }
}
