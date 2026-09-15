<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;
use App\Http\Resources\Mobile\BookingResource;
use App\Models\MobilePayment;
use App\Services\Mobile\MobileBookingService;
use App\Services\Mobile\MobilePaymentService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use RuntimeException;

class MobilePaymentController extends Controller
{
    use RespondsWithMobileApi;

    public function refresh(Request $request, $invoice, MobilePaymentService $payments, MobileBookingService $bookings)
    {
        try {
            $payments->refresh($payments->owned($request->user(), (int) $invoice));
            return $this->success((new BookingResource($bookings->findFor($request->user(), (int) $invoice)))->resolve($request), 'Payment status retrieved.');
        } catch (ConnectionException | RequestException $e) {
            return $this->failure('The payment provider could not be reached. Please check again shortly.', [], 503);
        } catch (RuntimeException $e) {
            return $this->failure($e->getMessage(), [], $this->exceptionStatus($e));
        }
    }

    public function checkout(Request $request, $payment, MobilePaymentService $payments)
    {
        abort_unless($request->hasValidSignature(), 403);
        $payment = MobilePayment::where('public_id', $payment)->firstOrFail();
        try {
            return $this->page(['form' => $payments->checkout($payment), 'message' => 'Continue to your payment provider.']);
        } catch (ConnectionException | RequestException $e) {
            return $this->page(['message' => 'The provider could not be reached. Return to the app and try again.'], 503);
        } catch (RuntimeException $e) {
            return $this->page(['message' => $e->getMessage()], $this->exceptionStatus($e));
        }
    }

    public function returned($payment, MobilePaymentService $payments)
    {
        $payment = MobilePayment::where('public_id', $payment)->firstOrFail();
        try {
            $payment = $payments->refresh($payment);
            $message = $payment->status === 'paid' ? 'Payment verified. Return to the Kainat Travels app to view your ticket.'
                : 'Return to the Kainat Travels app and check your payment status.';
        } catch (ConnectionException | RequestException | RuntimeException $e) {
            $message = 'Payment verification is still pending. Return to the app and check again shortly.';
        }
        return $this->page(['message' => $message]);
    }

    private function page(array $data, int $status = 200)
    {
        $data['sandbox'] = config('mobile_payments.environment') === 'sandbox';
        return response()->view('mobile.payment', $data, $status)->withHeaders([
            'Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer',
            'X-Frame-Options' => 'DENY', 'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; form-action https://sandbox.jazzcash.com.pk https://payments.jazzcash.com.pk https://onlinepayments.jazzcash.com.pk https://sandbox.bankalfalah.com https://payments.bankalfalah.com; base-uri 'none'; frame-ancestors 'none'",
        ]);
    }
}
