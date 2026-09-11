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
        $travelEyeService->verifyTicket($this->ticketId);
    }
}
