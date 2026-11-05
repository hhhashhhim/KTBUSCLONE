<?php

namespace App\Jobs\Mobile;

use App\Models\MobilePayment;
use App\Services\Mobile\MobilePaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class ReconcilePayment implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;
    public $uniqueFor = 300;
    public $paymentId;
    public $companyId;

    public function __construct(int $paymentId, int $companyId)
    {
        $this->paymentId = $paymentId;
        $this->companyId = $companyId;
        $this->onConnection(config('mobile.background_queue_connection'));
        $this->onQueue(config('mobile.background_queue_name', 'mobile'));
    }

    public function uniqueId(): string { return $this->companyId . ':' . $this->paymentId; }
    public function backoff(): array { return [15, 60, 120]; }

    public function handle(MobilePaymentService $payments): void
    {
        if ((int) config('mobile.company_id') !== $this->companyId) { return; }
        $payment = MobilePayment::where('company_id', $this->companyId)->find($this->paymentId);
        if (!$payment) { return; }
        try { $payments->refresh($payment); }
        catch (ConnectionException | RequestException $error) {
            // Do not serialize provider response bodies into failed-job exception logs.
            throw new RuntimeException('Mobile payment verification is temporarily unavailable.');
        }
    }
}
