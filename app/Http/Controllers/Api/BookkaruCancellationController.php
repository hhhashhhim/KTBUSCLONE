<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BookkaruApiLog;
use App\Models\Ticket;
use App\Services\TicketCancellationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BookkaruCancellationController extends Controller
{
    private $cancellationService;

    public function __construct(TicketCancellationService $cancellationService)
    {
        $this->cancellationService = $cancellationService;
    }

    public function cancelSeat(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            $response = [
                'status' => false,
                'message' => 'Unauthorized Bookkaru request.',
                'error_code' => 'UNAUTHORIZED',
            ];

            $this->writeLog($request, $response, 'unauthorized', false, false, true);
            Log::warning('Unauthorized Bookkaru cancellation request', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_id' => $request->input('request_id'),
            ]);

            return response()->json($response, 401);
        }

        $validator = Validator::make($request->all(), [
            'request_id' => ['required', 'string', 'max:191'],
            'invoice_id' => ['required', 'string', 'max:191'],
            'booking_reference' => ['nullable', 'string', 'max:191'],
            'seat_numbers' => ['required', 'array', 'min:1'],
            'seat_numbers.*' => ['required', 'string', 'max:20'],
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
            'source' => ['required', 'string', 'in:Bookkaru'],
        ], [
            'invoice_id.required' => 'Invoice ID is required.',
        ]);

        if ($validator->fails()) {
            $response = [
                'status' => 'error',
                'message' => 'Validation failed',
                'data' => null,
                'error' => $validator->errors()->toArray(),
            ];

            $this->writeLog($request, $response, 'validation_failed');

            return response()->json($response, 422);
        }

        $previousLog = BookkaruApiLog::where('request_id', $request->request_id)
            ->where('duplicate', false)
            ->where('unauthorized', false)
            ->whereNotNull('response_payload')
            ->oldest()
            ->first();

        if ($previousLog) {
            $response = $previousLog->response_payload;
            $this->writeLog($request, $response, 'duplicate', (bool) $previousLog->success, true);

            return response()->json($response, $previousLog->status == 'success' ? 200 : 422);
        }

        try {
            $response = DB::transaction(function () use ($request) {
                $invoiceId = $this->normalizeInvoiceId($request->invoice_id);
                $seatNumbers = collect($request->seat_numbers)->map(function ($seat) {
                    return (string) $seat;
                })->unique()->values()->all();

                if (!ctype_digit((string) $invoiceId)) {
                    return $this->businessError('Invalid invoice.', 'INVALID_INVOICE');
                }

                $invoiceExists = Ticket::withTrashed()
                    ->where('invoice_id', $invoiceId)
                    ->exists();

                if (!$invoiceExists) {
                    return $this->businessError('Invalid invoice.', 'INVALID_INVOICE');
                }

                $requestedTickets = Ticket::withTrashed()
                    ->where('invoice_id', $invoiceId)
                    ->whereIn('seat_no', $seatNumbers)
                    ->get();

                if ($requestedTickets->count() != count($seatNumbers)) {
                    return $this->businessError('Requested seat not found for this invoice.', 'SEAT_NOT_FOUND');
                }

                if ($request->filled('booking_reference')) {
                    $bookingReference = $this->normalizeBookingReference($request->booking_reference);
                    $mismatchedBooking = $requestedTickets->contains(function ($ticket) use ($bookingReference) {
                        return (string) $ticket->booking_no !== (string) $bookingReference;
                    });

                    if ($mismatchedBooking) {
                        return $this->businessError('Invalid booking reference.', 'INVALID_BOOKING_REFERENCE');
                    }
                }

                $alreadyCancelled = $requestedTickets->contains(function ($ticket) {
                    return $ticket->trashed() || $ticket->type == 'canceled';
                });

                if ($alreadyCancelled) {
                    return $this->businessError('Ticket or seat is already cancelled.', 'ALREADY_CANCELLED');
                }

                $tickets = Ticket::where('invoice_id', $invoiceId)
                    ->whereIn('seat_no', $seatNumbers)
                    ->lockForUpdate()
                    ->get();

                if ($tickets->count() != count($seatNumbers)) {
                    return $this->businessError('Ticket or seat is already cancelled.', 'ALREADY_CANCELLED');
                }

                foreach ($tickets as $ticket) {
                    $this->cancellationService->cancelTicket(
                        $ticket,
                        0,
                        $request->cancellation_reason ?: 'Cancelled from Bookkaru',
                        null
                    );
                }

                $firstTicket = $tickets->first();
                ActivityLog::create([
                    'activity_by' => 0,
                    'message' => 'Bookkaru | canceled booking. seat no ' . implode(',', $seatNumbers) . ' | invoice id: ' . $invoiceId,
                    'requested_host' => request()->ip(),
                    'company_id' => $firstTicket ? $firstTicket->company_id : null,
                ]);

                $cancelledAt = Carbon::now()->format('Y-m-d H:i:s');

                return [
                    'status' => true,
                    'message' => 'Seat cancellation successful.',
                    'data' => [
                        'request_id' => $request->request_id,
                        'invoice_id' => $request->invoice_id,
                        'booking_reference' => $request->booking_reference,
                        'cancelled_seats' => $seatNumbers,
                        'cancelled_at' => $cancelledAt,
                    ],
                ];
            });

            $success = isset($response['status']) && $response['status'] === true;
            $this->writeLog($request, $response, $success ? 'success' : 'failed', $success);

            return response()->json($response, $success ? 200 : 422);
        } catch (\Exception $e) {
            Log::error('Bookkaru cancellation exception: ' . $e->getMessage(), [
                'request_id' => $request->input('request_id'),
                'trace' => $e->getTraceAsString(),
            ]);

            $response = [
                'status' => false,
                'message' => 'Unable to process cancellation request.',
                'error_code' => 'SERVER_ERROR',
            ];

            $this->writeLog($request, $response, 'exception', false, false, false, $e->getMessage());

            return response()->json($response, 500);
        }
    }

    private function isAuthorized(Request $request)
    {
        $configuredKey = config('services.bookkaru.api_key');
        $providedKey = $request->header('X-BOOKKARU-API-KEY');

        return $configuredKey && $providedKey && hash_equals($configuredKey, $providedKey);
    }

    private function normalizeInvoiceId($invoiceId)
    {
        if (preg_match('/^INV-(\d+)$/i', $invoiceId, $matches)) {
            return $matches[1];
        }

        return $invoiceId;
    }

    private function normalizeBookingReference($bookingReference)
    {
        if (preg_match('/^PNR(\d+)$/i', $bookingReference, $matches)) {
            return $matches[1];
        }

        return $bookingReference;
    }

    private function businessError($message, $errorCode)
    {
        return [
            'status' => false,
            'message' => $message,
            'error_code' => $errorCode,
        ];
    }

    private function writeLog(Request $request, array $response, $status, $success = false, $duplicate = false, $unauthorized = false, $exceptionMessage = null)
    {
        BookkaruApiLog::create([
            'request_id' => $request->input('request_id'),
            'invoice_id' => $request->input('invoice_id'),
            'booking_reference' => $request->input('booking_reference'),
            'seat_numbers' => $request->input('seat_numbers'),
            'request_payload' => $request->all(),
            'response_payload' => $response,
            'status' => $status,
            'success' => $success,
            'duplicate' => $duplicate,
            'unauthorized' => $unauthorized,
            'exception_message' => $exceptionMessage,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
