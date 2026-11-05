<?php

namespace App\Jobs\Mobile;

use App\Services\Mobile\MobileNotificationDelivery;
use App\Services\Mobile\MobilePushGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeliverNotification implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // The delivery ledger controls retries and backoff, including worker crashes.
    public $tries = 1;
    public $timeout = 45;
    public $uniqueFor = 300;
    public $deliveryId;
    public $companyId;

    public function __construct(int $deliveryId, int $companyId)
    {
        $this->deliveryId = $deliveryId;
        $this->companyId = $companyId;
        $this->onConnection(config('mobile.background_queue_connection'));
        $this->onQueue(config('mobile.background_queue_name', 'mobile'));
    }

    public function uniqueId(): string { return $this->companyId . ':' . $this->deliveryId; }

    public function handle(MobileNotificationDelivery $delivery, MobilePushGateway $gateway): void
    {
        if ((int) config('mobile.company_id') === $this->companyId) {
            $delivery->deliver($this->deliveryId, $gateway);
        }
    }
}
