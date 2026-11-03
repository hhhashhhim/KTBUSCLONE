<?php

namespace App\Jobs;

use App\Services\TravelEyeService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyTravelEyeTicket
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $ticketId;

    public function __construct($ticketId)
    {
        $this->ticketId = (int) $ticketId;
    }

    public function handle(TravelEyeService $travelEyeService)
    {
        try {
            $travelEyeService->verifyTicket($this->ticketId);
        } catch (\Throwable $exception) {
            // Missing log tables, invalid encrypted settings, or payload errors
            // must not escape the after-response callback for a booked ticket.
            \Illuminate\Support\Facades\Log::error('Travel Eye background verification could not complete.', [
                'ticket_id' => $this->ticketId,
                'exception' => get_class($exception),
            ]);
        }
    }
}
