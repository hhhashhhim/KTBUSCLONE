<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;
use App\Http\Requests\Mobile\CreateMobileBookingRequest;
use App\Http\Requests\Mobile\QuoteBookingRequest;
use App\Http\Resources\Mobile\BookingResource;
use App\Services\Mobile\MobileBookingService;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class MobileBookingController extends Controller
{
    use RespondsWithMobileApi;

    public function quote(QuoteBookingRequest $request, MobileBookingService $bookings)
    {
        try {
            $quote = $bookings->quote($request->user(), $request->validated());
            return $this->success([
                'quote_token' => $quote->token,
                'expires_at' => $quote->expires_at->toIso8601String(),
                'base_fare' => (float) $quote->base_fare,
                'taxes' => (float) $quote->taxes,
                'fees' => (float) $quote->fees,
                'discount' => (float) $quote->discount,
                'wallet_deduction' => (float) data_get($quote->payload, 'wallet.deduction', 0),
                'wallet_points_used' => (int) data_get($quote->payload, 'wallet.points', 0),
                'total' => (float) $quote->total,
                'currency' => 'PKR',
                'payment_methods' => $bookings->paymentMethods(),
                'payment_environment' => config('mobile_payments.environment'),
                'payment_preview' => (bool) config('mobile_payments.preview_only'),
            ], 'Fare quote created.');
        } catch (Throwable $exception) {
            return $this->bookingFailure($exception);
        }
    }

    public function store(CreateMobileBookingRequest $request, MobileBookingService $bookings)
    {
        try {
            $booking = $bookings->create(
                $request->user(),
                $request->quote_token,
                $request->payment_method
            );
            return $this->success((new BookingResource($booking))->resolve($request), 'Booking created.', 201);
        } catch (Throwable $exception) {
            return $this->bookingFailure($exception);
        }
    }

    public function index(Request $request, MobileBookingService $bookings)
    {
        try {
            $page = max(1, (int) $request->query('page', 1));
            $perPage = min(50, max(1, (int) $request->query('per_page', 20)));
            $result = $bookings->listFor($request->user(), $page, $perPage);
            $items = collect($result['items'])->map(function ($booking) use ($request) {
                return (new BookingResource($booking))->resolve($request);
            });
            return $this->success([
                'items' => $items,
                'pagination' => $result['pagination'],
            ], 'Bookings retrieved.');
        } catch (Throwable $exception) {
            return $this->bookingFailure($exception);
        }
    }

    public function show(Request $request, $invoice, MobileBookingService $bookings)
    {
        try {
            $booking = $bookings->findFor($request->user(), (int) $invoice);
            return $this->success((new BookingResource($booking))->resolve($request), 'Booking retrieved.');
        } catch (Throwable $exception) {
            return $this->bookingFailure($exception);
        }
    }

    private function bookingFailure(Throwable $exception)
    {
        // Booking services use plain RuntimeException for intentional business errors.
        // Subclasses such as QueryException must never expose their internal messages,
        // even when a database/driver error code happens to resemble an HTTP status.
        $isBusinessException = get_class($exception) === RuntimeException::class
            && $exception->getPrevious() === null;
        $status = $isBusinessException ? $this->exceptionStatus($exception) : 500;

        if ($isBusinessException && $status >= 400 && $status < 500) {
            return $this->failure($exception->getMessage(), [], $status);
        }

        report($exception);

        return $this->failure('Something went wrong. Please try again.', [], $status);
    }
}
