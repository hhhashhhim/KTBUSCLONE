<?php

namespace App\Console\Commands;

use App\Models\MobilePayment;
use App\Services\Mobile\MobilePaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ReconcileMobilePayments extends Command
{
    protected $signature = 'mobile:reconcile-payments';
    protected $description = 'Verify mobile payments and release expired mobile reservations';

    public function handle(MobilePaymentService $payments): int
    {
        if (config('mobile_payments.preview_only') || !Schema::hasTable('mobile_payments')) { return 0; }
        MobilePayment::where(function ($query) {
            $query->where('status', 'pending')->orWhere(function ($query) {
                $query->where('status', 'expired')->whereNotNull('started_at')
                    ->where('created_at', '>=', now()->subDays(7))->where(function ($query) {
                        $query->whereNull('checked_at')->orWhere('checked_at', '<=', now()->subMinutes(30));
                    });
            });
        })->chunkById(100, function ($rows) use ($payments) {
            foreach ($rows as $payment) {
                try { $payments->refresh($payment); }
                catch (\Throwable $e) {
                    Log::warning('Mobile payment reconciliation needs attention.', ['payment_id' => $payment->public_id]);
                }
            }
        });
        return 0;
    }
}
