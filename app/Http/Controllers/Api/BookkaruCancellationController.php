<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ValidationResource;
use App\Models\ActivityLog;
use App\Models\BookkaruApiLog;
use App\Models\Ticket;
use App\Services\TicketCancellationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;

class BookkaruCancellationController extends Controller
{
    private $cancellationService;

    public function __construct(TicketCancellationService $cancellationService)
    {
        $this->cancellationService = $cancellationService;
    }

    public function cancelSeat(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            $this->cancelSeatValidationRules($request),
            $this->cancelSeatValidationMessages($request)
        );

        if ($validator->fails()) {
            return new ValidationResource($validator->errors());
        }

        if (!$this->isAuthorized($request)) {
            $response = [
                'status' => 'error',
                'message' => 'Unauthorized Bookkaru request.',
                'data' => null,
                'error' => ['code' => 'UNAUTHORIZED'],
            ];

            $this->storeUnauthorizedLog($request, $response);

            Log::warning('Unauthorized Bookkaru cancellation request', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_id' => $request->input('request_id'),
            ]);

            return response()->json($response, 401);
        }

        $payload = $this->normalizeCancelSeatPayload($request);
        $requestId = $payload['request_id'];
        $invoiceId = $payload['invoice_id'];
        $seatNumbers = $payload['seat_numbers'];
        $bookingReference = $payload['booking_reference'];
        $source = $payload['source'];
        $refundPercentage = $payload['refund_percentage'];

        if ($invoiceId === null) {
            $response = $this->businessError('Invalid invoice.', 'INVALID_INVOICE');
            $this->storeFailureLog($request, $requestId, null, $seatNumbers, $bookingReference, $source, $response, 'failed');

            return response()->json($response, 422);
        }

        $existing = BookkaruApiLog::where('request_id', $requestId)->first();
        if ($existing) {
            $existing->duplicate = true;
            $existing->save();

            $response = $this->buildResponseFromLog($existing);
            $existing->response_payload = $response;
            $existing->save();

            ActivityLog::create([
                'activity_by' => 0,
                'message' => 'Bookkaru | duplicate cancellation request received | request id: ' . $requestId,
                'requested_host' => $request->ip(),
                'company_id' => $existing->company_id,
            ]);

            return response()->json($response, 200);
        }

        try {
            $ticketValidation = $this->validateTicketsForRequest($invoiceId, $seatNumbers, $bookingReference);
            if ($ticketValidation !== true) {
                $this->storeFailureLog($request, $requestId, $invoiceId, $seatNumbers, $bookingReference, $source, $ticketValidation, 'failed');

                return response()->json($ticketValidation, 422);
            }

            $requestPayload = array_merge($request->all(), [
                'refund_percentage' => $refundPercentage,
            ]);
            $pendingResponse = $this->buildPendingResponse($requestId, $invoiceId, $seatNumbers, $bookingReference, $source, $refundPercentage);

            $log = BookkaruApiLog::create([
                'request_id' => $requestId,
                'invoice_id' => $invoiceId,
                'normalized_invoice_id' => $invoiceId,
                'booking_reference' => $bookingReference,
                'seat_numbers' => $seatNumbers,
                'request_payload' => $requestPayload,
                'response_payload' => $pendingResponse,
                'status' => 'pending',
                'approval_status' => 'pending',
                'cancellation_status' => 'pending',
                'success' => true,
                'duplicate' => false,
                'unauthorized' => false,
                'exception_message' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            ActivityLog::create([
                'activity_by' => 0,
                'message' => 'Bookkaru | cancellation request received | invoice id: ' . $invoiceId . ' | seats: ' . implode(',', $seatNumbers),
                'requested_host' => $request->ip(),
                'company_id' => $this->getCompanyIdForRequest($invoiceId, $seatNumbers),
            ]);

            return response()->json($pendingResponse, 202);
        } catch (\Throwable $e) {
            Log::error('Bookkaru cancellation request exception: ' . $e->getMessage(), [
                'request_id' => $requestId,
                'trace' => $e->getTraceAsString(),
            ]);

            $response = [
                'status' => 'error',
                'message' => 'Unable to process cancellation request.',
                'data' => null,
                'error' => ['code' => 'SERVER_ERROR'],
            ];

            $this->storeFailureLog($request, $requestId, $invoiceId, $seatNumbers, $bookingReference, $source, $response, 'failed', $e->getMessage());

            return response()->json($response, 500);
        }
    }

    public function requests(Request $request)
    {
        if (!checkForSubmenu('bookkaru-cancellation')) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }

        $query = BookkaruApiLog::with(['approvedBy:id,name', 'rejectedBy:id,name'])
            ->when($request->request_id, function ($builder) use ($request) {
                $builder->where('request_id', 'like', '%' . trim((string) $request->request_id) . '%');
            })
            ->when($request->invoice_id !== null && $request->invoice_id !== '', function ($builder) use ($request) {
                $invoiceId = $this->normalizeInvoiceId($request->invoice_id);
                if ($invoiceId !== null) {
                    $builder->where('invoice_id', $invoiceId);
                }
            })
            ->when($request->booking_reference, function ($builder) use ($request) {
                $builder->where('booking_reference', 'like', '%' . trim((string) $request->booking_reference) . '%');
            })
            ->when($request->status, function ($builder) use ($request) {
                $builder->where('status', trim((string) $request->status));
            })
            ->when($request->from_date, function ($builder) use ($request) {
                $builder->whereDate('created_at', '>=', $request->from_date);
            })
            ->when($request->to_date, function ($builder) use ($request) {
                $builder->whereDate('created_at', '<=', $request->to_date);
            })
            ->orderByDesc('id');

        $requests = $query->limit(200)->get()->map(function (BookkaruApiLog $log) {
            return $this->formatPortalRow($log);
        });

        return response()->json([
            'status' => true,
            'message' => 'Bookkaru cancellation requests fetched successfully.',
            'data' => [
                'requests' => $requests,
            ],
        ]);
    }

    public function approveRequest(Request $request)
    {
        if (!checkPermissionButtons('approve-request')) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }

        $validator = Validator::make($request->all(), [
            'id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'data' => null,
                'error' => $validator->errors()->toArray(),
            ], 422);
        }

        $log = BookkaruApiLog::with(['approvedBy:id,name', 'rejectedBy:id,name'])->find($request->id);
        if (!$log) {
            return response()->json($this->businessError('Request not found.', 'NOT_FOUND'), 404);
        }

        if (in_array($log->status, ['approved', 'cancelled', 'rejected'], true)) {
            return response()->json($this->buildResponseFromLog($log), 200);
        }

        $invoiceId = (int) $log->normalized_invoice_id;
        $seatNumbers = collect($log->seat_numbers ?? [])->map(function ($seat) {
            return trim((string) $seat);
        })->filter()->values()->all();
        $bookingReference = trim((string) $log->booking_reference);
        $refundPercentage = $this->normalizeRefundPercentage(data_get($log->request_payload, 'refund_percentage', config('services.bookkaru.refund_percentage', 0)));

        $ticketValidation = $this->validateTicketsForRequest($invoiceId, $seatNumbers, $bookingReference ?: null);
        if ($ticketValidation !== true) {
            $response = $this->businessError('Cancellation approval failed.', 'CANCELLATION_FAILED');
            $this->applyFailureState($log, $response, auth()->id(), 'approve');

            return response()->json($response, 422);
        }

        try {
            $now = Carbon::now();
            $cancelledSeats = [];
            $refunds = [];
            $response = null;

            DB::transaction(function () use ($invoiceId, $seatNumbers, $log, $now, $refundPercentage, &$cancelledSeats, &$refunds, &$response) {
                $tickets = Ticket::where('invoice_id', $invoiceId)
                    ->whereIn('seat_no', $seatNumbers)
                    ->lockForUpdate()
                    ->get();

                if ($tickets->count() !== count($seatNumbers)) {
                    throw new \RuntimeException('CANCELLATION_FAILED');
                }

                foreach ($tickets as $ticket) {
                    $this->cancellationService->cancelTicket(
                        $ticket,
                        $refundPercentage,
                        $log->request_payload['cancellation_reason'] ?? 'Cancelled from Bookkaru',
                        auth()->id()
                    );
                    $cancelledSeats[] = (string) $ticket->seat_no;
                    $refunds[] = [
                        'ticket_id' => $ticket->id,
                        'seat_no' => (string) $ticket->seat_no,
                        'seat_fare' => (float) $ticket->seat_fare,
                        'refund_percentage' => $refundPercentage,
                        'refund_amount' => round(((float) $ticket->seat_fare * $refundPercentage) / 100, 2),
                        'refund_reason' => $log->request_payload['cancellation_reason'] ?? 'Cancelled from Bookkaru',
                    ];
                }

                $response = [
                    'status' => 'success',
                    'message' => 'Seat cancellation successful.',
                    'data' => [
                        'request_id' => $log->request_id,
                        'invoice_id' => $log->invoice_id,
                        'booking_reference' => $log->booking_reference,
                        'cancelled_seats' => $cancelledSeats,
                        'refund_percentage' => $refundPercentage,
                        'refunds' => $refunds,
                        'approved_at' => $now->format('Y-m-d H:i:s'),
                        'cancelled_at' => $now->format('Y-m-d H:i:s'),
                    ],
                    'error' => null,
                ];

                $log->update([
                    'status' => 'cancelled',
                    'approval_status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => $now,
                    'cancellation_status' => 'cancelled',
                    'cancelled_at' => $now,
                    'success' => true,
                    'response_payload' => $response,
                ]);
            });

            ActivityLog::create([
                'activity_by' => auth()->id() ?? 0,
                'message' => Auth::user()->name . ' | approved Bookkaru cancellation request | request id: ' . $log->request_id,
                'requested_host' => request()->ip(),
                'company_id' => Auth::user()->company_id,
            ]);

            return response()->json($response, 200);
        } catch (\Throwable $e) {
            Log::error('Bookkaru approval exception: ' . $e->getMessage(), [
                'request_id' => $log->request_id,
                'trace' => $e->getTraceAsString(),
            ]);

            $response = $this->businessError('Cancellation approval failed.', 'CANCELLATION_FAILED');
            $this->applyFailureState($log, $response, auth()->id(), 'approve', $e->getMessage());

            return response()->json($response, 500);
        }
    }

    public function rejectRequest(Request $request)
    {
        if (!checkPermissionButtons('reject-request')) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }

        $validator = Validator::make($request->all(), [
            'id' => ['required', 'integer'],
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'data' => null,
                'error' => $validator->errors()->toArray(),
            ], 422);
        }

        $log = BookkaruApiLog::find($request->id);
        if (!$log) {
            return response()->json($this->businessError('Request not found.', 'NOT_FOUND'), 404);
        }

        if (in_array($log->status, ['rejected', 'cancelled'], true)) {
            return response()->json($this->buildResponseFromLog($log), 200);
        }

        $now = Carbon::now();
        $response = [
            'status' => true,
            'message' => 'Cancellation request rejected.',
            'data' => [
                'request_id' => $log->request_id,
                'invoice_id' => $log->invoice_id,
                'booking_reference' => $log->booking_reference,
                'rejection_reason' => $request->rejection_reason,
                'rejected_at' => $now->format('Y-m-d H:i:s'),
                'approval_status' => 'rejected',
                'cancellation_status' => 'rejected',
            ],
        ];

        $log->update([
            'status' => 'rejected',
            'approval_status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejected_at' => $now,
            'rejection_reason' => $request->rejection_reason,
            'cancellation_status' => 'rejected',
            'success' => true,
            'response_payload' => $response,
        ]);

        ActivityLog::create([
            'activity_by' => auth()->id() ?? 0,
            'message' => Auth::user()->name . ' | rejected Bookkaru cancellation request | request id: ' . $log->request_id,
            'requested_host' => request()->ip(),
            'company_id' => Auth::user()->company_id,
        ]);

        return response()->json($response, 200);
    }

    private function cancelSeatValidationRules(Request $request)
    {
        return [
            'request_id' => ['required', 'string', 'max:191'],
            'invoice_id' => ['required'],
            'booking_reference' => ['required', 'string', 'max:191'],
            'seat_numbers' => ['required', 'array', 'min:1'],
            'seat_numbers.*' => ['required', 'string', 'max:20'],
            'cancellation_reason' => ['required', 'string', 'max:500'],
            'source' => ['required', 'string', 'in:Bookkaru'],
            'refund_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    private function cancelSeatValidationMessages(Request $request)
    {
        return [];
    }

    private function normalizeCancelSeatPayload(Request $request)
    {
        return [
            'request_id' => trim((string) $request->request_id),
            'invoice_id' => $this->normalizeInvoiceId($request->invoice_id),
            'seat_numbers' => $this->normalizeSeatNumbers($request->seat_numbers),
            'booking_reference' => trim((string) $request->booking_reference),
            'cancellation_reason' => trim((string) $request->cancellation_reason),
            'source' => trim((string) $request->source),
            'refund_percentage' => $this->resolveRefundPercentage($request),
        ];
    }

    private function resolveRefundPercentage(Request $request)
    {
        if ($request->filled('refund_percentage')) {
            return $this->normalizeRefundPercentage($request->refund_percentage);
        }

        return $this->normalizeRefundPercentage(config('services.bookkaru.refund_percentage', 0));
    }

    private function normalizeRefundPercentage($percentage)
    {
        $percentage = is_numeric($percentage) ? (float) $percentage : 0;

        if ($percentage < 0) {
            return 0;
        }

        if ($percentage > 100) {
            return 100;
        }

        return round($percentage, 2);
    }

    private function isAuthorized(Request $request)
    {
        $configuredKey = config('services.bookkaru.api_key');
        $providedKey = $request->header('X-BOOKKARU-API-KEY');

        if ($configuredKey && $providedKey && hash_equals($configuredKey, $providedKey)) {
            return true;
        }

        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return false;
        }

        $accessTokenModel = Sanctum::personalAccessTokenModel();
        $accessToken = $accessTokenModel::findToken($bearerToken);

        if (!$accessToken || !$accessToken->tokenable) {
            return false;
        }

        $allowedEmail = config('services.bookkaru.user_email');

        return $allowedEmail
            && strcasecmp($accessToken->tokenable->email, $allowedEmail) === 0;
    }

    private function normalizeInvoiceId($invoiceId)
    {
        $invoiceId = trim((string) $invoiceId);

        if (preg_match('/^INV-\s*(\d+)$/i', $invoiceId, $matches)) {
            return (int) $matches[1];
        }

        if (is_numeric($invoiceId)) {
            return (int) $invoiceId;
        }

        return null;
    }

    private function normalizeSeatNumbers($seatNumbers)
    {
        return collect($seatNumbers)
            ->map(function ($seat) {
                return trim((string) $seat);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeBookingReference($bookingReference)
    {
        $bookingReference = trim((string) $bookingReference);

        return $bookingReference === '' ? null : $bookingReference;
    }

    private function validateTicketsForRequest($invoiceId, array $seatNumbers, $bookingReference = null)
    {
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

        if ($requestedTickets->count() !== count($seatNumbers)) {
            return $this->businessError('Requested seat not found for this invoice.', 'SEAT_NOT_FOUND');
        }

        if ($bookingReference !== null) {
            $mismatchedBooking = $requestedTickets->contains(function ($ticket) use ($bookingReference) {
                return trim((string) $ticket->transaction_id) !== trim((string) $bookingReference);
            });

            if ($mismatchedBooking) {
                return $this->businessError('Invalid booking reference.', 'INVALID_BOOKING_REFERENCE');
            }
        }

        $alreadyCancelled = $requestedTickets->contains(function ($ticket) {
            return $ticket->trashed() || $ticket->type === 'canceled';
        });

        if ($alreadyCancelled) {
            return $this->businessError('Ticket or seat is already cancelled.', 'ALREADY_CANCELLED');
        }

        return true;
    }

    private function buildPendingResponse($requestId, $invoiceId, array $seatNumbers, $bookingReference, $source, $refundPercentage)
    {
        return [
            'status' => 'success',
            'message' => 'Cancellation request received and is pending approval.',
            'data' => [
                'request_id' => $requestId,
                'invoice_id' => $invoiceId,
                'booking_reference' => $bookingReference,
                'seat_numbers' => $seatNumbers,
                'refund_percentage' => $refundPercentage,
                'approval_status' => 'pending',
                'cancellation_status' => 'pending',
                'source' => $source,
            ],
            'error' => null,
        ];
    }

    private function buildResponseFromLog(BookkaruApiLog $log)
    {
        if (is_array($log->response_payload) && !empty($log->response_payload)) {
            return $log->response_payload;
        }

        return [
            'status' => $log->status !== 'failed' ? 'success' : 'error',
            'message' => $this->messageForStatus($log->current_status),
            'data' => [
                'request_id' => $log->request_id,
                'invoice_id' => $log->invoice_id,
                'booking_reference' => $log->booking_reference,
                'seat_numbers' => $log->seat_numbers ?? [],
                'approval_status' => $log->approval_status ?? 'pending',
                'cancellation_status' => $log->cancellation_status ?? 'pending',
                'source' => data_get($log->request_payload, 'source', 'Bookkaru'),
                'approved_at' => optional($log->approved_at)->format('Y-m-d H:i:s'),
                'rejected_at' => optional($log->rejected_at)->format('Y-m-d H:i:s'),
                'cancelled_at' => optional($log->cancelled_at)->format('Y-m-d H:i:s'),
                'rejection_reason' => $log->rejection_reason,
            ],
            'error' => null,
        ];
    }

    private function messageForStatus($status)
    {
        switch ($status) {
            case 'cancelled':
                return 'Seat cancellation successful.';
            case 'approved':
                return 'Cancellation request approved.';
            case 'rejected':
                return 'Cancellation request rejected.';
            case 'failed':
                return 'Cancellation request failed.';
            default:
                return 'Cancellation request received and is pending approval.';
        }
    }

    private function formatPortalRow(BookkaruApiLog $log)
    {
        return [
            'id' => $log->id,
            'request_id' => $log->request_id,
            'invoice_id' => $log->invoice_id,
            'normalized_invoice_id' => $log->normalized_invoice_id,
            'booking_reference' => $log->booking_reference,
            'seat_numbers' => $log->seat_numbers ?? [],
            'cancellation_reason' => data_get($log->request_payload, 'cancellation_reason'),
            'source' => data_get($log->request_payload, 'source', 'Bookkaru'),
            'status' => $log->current_status,
            'approval_status' => $log->approval_status,
            'cancellation_status' => $log->cancellation_status,
            'request_payload' => $log->request_payload,
            'response_payload' => $log->response_payload,
            'request_date' => optional($log->created_at)->format('Y-m-d H:i:s'),
            'approved_at' => optional($log->approved_at)->format('Y-m-d H:i:s'),
            'rejected_at' => optional($log->rejected_at)->format('Y-m-d H:i:s'),
            'cancelled_at' => optional($log->cancelled_at)->format('Y-m-d H:i:s'),
            'rejection_reason' => $log->rejection_reason,
            'approved_by' => optional($log->approvedBy)->name,
            'rejected_by' => optional($log->rejectedBy)->name,
        ];
    }

    private function businessError($message, $errorCode)
    {
        return [
            'status' => 'error',
            'message' => $message,
            'data' => null,
            'error' => ['code' => $errorCode],
        ];
    }

    private function storeUnauthorizedLog(Request $request, array $response)
    {
        BookkaruApiLog::create([
            'request_id' => $request->input('request_id'),
            'invoice_id' => $this->normalizeInvoiceId($request->input('invoice_id')),
            'normalized_invoice_id' => $this->normalizeInvoiceId($request->input('invoice_id')),
            'booking_reference' => $this->normalizeBookingReference($request->input('booking_reference')),
            'seat_numbers' => $this->normalizeSeatNumbers($request->input('seat_numbers', [])),
            'request_payload' => $request->all(),
            'response_payload' => $response,
            'status' => 'unauthorized',
            'approval_status' => 'unauthorized',
            'cancellation_status' => 'unauthorized',
            'success' => false,
            'duplicate' => false,
            'unauthorized' => true,
            'exception_message' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function storeFailureLog(Request $request, $requestId, $invoiceId, array $seatNumbers, $bookingReference, $source, array $response, $status, $exceptionMessage = null)
    {
        if (!$requestId) {
            return;
        }

        BookkaruApiLog::create([
            'request_id' => $requestId,
            'invoice_id' => $invoiceId,
            'normalized_invoice_id' => $invoiceId,
            'booking_reference' => $bookingReference,
            'seat_numbers' => $seatNumbers,
            'request_payload' => $request->all(),
            'response_payload' => $response,
            'status' => $status,
            'approval_status' => $status,
            'cancellation_status' => $status,
            'success' => false,
            'duplicate' => false,
            'unauthorized' => false,
            'exception_message' => $exceptionMessage,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function applyFailureState(BookkaruApiLog $log, array $response, $userId = null, $action = 'approve', $exceptionMessage = null)
    {
        $attrs = [
            'status' => 'failed',
            'approval_status' => $log->approval_status === 'rejected' ? 'rejected' : 'approved',
            'cancellation_status' => 'failed',
            'success' => false,
            'response_payload' => $response,
            'exception_message' => $exceptionMessage,
        ];

        if ($action === 'approve') {
            $attrs['approved_by'] = $userId;
            $attrs['approved_at'] = Carbon::now();
        }

        $log->update($attrs);
    }

    private function getCompanyIdForRequest($invoiceId, array $seatNumbers)
    {
        $ticket = Ticket::where('invoice_id', $invoiceId)
            ->whereIn('seat_no', $seatNumbers)
            ->first();

        return $ticket ? $ticket->company_id : null;
    }
}
