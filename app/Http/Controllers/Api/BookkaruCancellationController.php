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
                'message' => 'Unauthorized online terminal request.',
                'data' => null,
                'error' => ['code' => 'UNAUTHORIZED'],
            ];

            $this->storeUnauthorizedLog($request, $response);

            Log::warning('Unauthorized online terminal cancellation request', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_id' => $request->input('request_id'),
                'source' => $request->input('source'),
            ]);

            return response()->json($response, 401);
        }

        $payload = $this->normalizeCancelSeatPayload($request);
        $requestId = $payload['request_id'];
        $invoiceId = $payload['invoice_id'];
        $seatNumbers = $payload['seat_numbers'];
        $transactionId = $payload['transaction_id'];
        $source = $payload['source'];
        $deductionPercentage = $payload['deduction_percentage'];
        $refundPercentage = $payload['refund_percentage'];

        if ($invoiceId === null) {
            $response = $this->businessError('Invalid invoice.', 'INVALID_INVOICE');
            $this->storeFailureLog($request, $requestId, null, $seatNumbers, $transactionId, $source, $response, 'failed');

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
                'message' => $source . ' | duplicate cancellation request received | request id: ' . $requestId,
                'requested_host' => $request->ip(),
                'company_id' => $existing->company_id,
            ]);

            return response()->json($response, 200);
        }

        try {
            $existingSeatRequest = $this->findExistingSeatRequest($requestId, $invoiceId, $transactionId, $seatNumbers);
            if ($existingSeatRequest) {
                $response = $this->businessError('Cancellation request already exists for this seat.', 'DUPLICATE_SEAT_CANCELLATION');
                $this->storeFailureLog($request, $requestId, $invoiceId, $seatNumbers, $transactionId, $source, $response, 'failed');

                return response()->json($response, 409);
            }

            $ticketValidation = $this->validateTicketsForRequest($invoiceId, $seatNumbers, $transactionId);
            if ($ticketValidation !== true) {
                $this->storeFailureLog($request, $requestId, $invoiceId, $seatNumbers, $transactionId, $source, $ticketValidation, 'failed');

                return response()->json($ticketValidation, 422);
            }

            $requestPayload = array_merge($request->all(), [
                'transaction_id' => $transactionId,
                'booking_reference' => $transactionId,
                'deduction_percentage' => $deductionPercentage,
                'refund_percentage' => $refundPercentage,
                'source' => $source,
            ]);
            $pendingResponse = $this->buildPendingResponse(
                $requestId,
                $invoiceId,
                $seatNumbers,
                $transactionId,
                $source,
                $deductionPercentage,
                $refundPercentage,
                $payload['cancellation_reason']
            );

            $log = BookkaruApiLog::create([
                'request_id' => $requestId,
                'invoice_id' => $invoiceId,
                'normalized_invoice_id' => $invoiceId,
                'booking_reference' => $transactionId,
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
                'message' => $source . ' | cancellation request received | invoice id: ' . $invoiceId . ' | seats: ' . implode(',', $seatNumbers),
                'requested_host' => $request->ip(),
                'company_id' => $this->getCompanyIdForRequest($invoiceId, $seatNumbers),
            ]);

            return response()->json($pendingResponse, 200);
        } catch (\Throwable $e) {
            Log::error('Online terminal cancellation request exception: ' . $e->getMessage(), [
                'request_id' => $requestId,
                'source' => $source,
                'trace' => $e->getTraceAsString(),
            ]);

            $response = [
                'status' => 'error',
                'message' => 'Unable to process cancellation request.',
                'data' => null,
                'error' => ['code' => 'SERVER_ERROR'],
            ];

            $this->storeFailureLog($request, $requestId, $invoiceId, $seatNumbers, $transactionId, $source, $response, 'failed', $e->getMessage());

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
            ->when($request->transaction_id, function ($builder) use ($request) {
                $builder->where('booking_reference', 'like', '%' . trim((string) $request->transaction_id) . '%');
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
            'message' => 'Online terminal cancellation requests fetched successfully.',
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
        $transactionId = trim((string) data_get($log->request_payload, 'transaction_id', $log->booking_reference));
        $deductionPercentage = $this->resolveLogDeductionPercentage($log);
        $refundPercentage = $this->refundPercentageFromDeduction($deductionPercentage);

        $ticketValidation = $this->validateTicketsForRequest($invoiceId, $seatNumbers, $transactionId ?: null);
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

            DB::transaction(function () use ($invoiceId, $seatNumbers, $log, $now, $transactionId, $deductionPercentage, $refundPercentage, &$cancelledSeats, &$refunds, &$response) {
                $tickets = Ticket::where('invoice_id', $invoiceId)
                    ->whereIn('seat_no', $seatNumbers)
                    ->lockForUpdate()
                    ->get();

                if ($tickets->count() !== count($seatNumbers)) {
                    throw new \RuntimeException('CANCELLATION_FAILED');
                }

                foreach ($tickets as $ticket) {
                    $seatFare = (float) $ticket->seat_fare;
                    $deductionAmount = $this->calculatePercentageAmount($seatFare, $deductionPercentage);
                    $refundAmount = $this->calculatePercentageAmount($seatFare, $refundPercentage);

                    $this->cancellationService->cancelTicket(
                        $ticket,
                        $refundPercentage,
                        $log->request_payload['cancellation_reason'] ?? 'Cancelled from online terminal',
                        auth()->id()
                    );
                    $cancelledSeats[] = (string) $ticket->seat_no;
                    $refunds[] = [
                        'ticket_id' => $ticket->id,
                        'seat_no' => (string) $ticket->seat_no,
                        'transaction_id' => $transactionId,
                        'seat_fare' => $seatFare,
                        'deduction_percentage' => $deductionPercentage,
                        'deduction_amount' => $deductionAmount,
                        'refund_percentage' => $refundPercentage,
                        'refund_amount' => $refundAmount,
                        'refund_reason' => $log->request_payload['cancellation_reason'] ?? 'Cancelled from online terminal',
                    ];
                }

                $response = [
                    'status' => 'success',
                    'message' => 'Seat cancellation successful.',
                    'data' => [
                        'request_id' => $log->request_id,
                        'invoice_id' => $log->invoice_id,
                        'transaction_id' => $transactionId,
                        'cancelled_seats' => $cancelledSeats,
                        'deduction_percentage' => $deductionPercentage,
                        'deduction_amount' => round(collect($refunds)->sum('deduction_amount'), 2),
                        'refund_percentage' => $refundPercentage,
                        'refund_amount' => round(collect($refunds)->sum('refund_amount'), 2),
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
                'message' => Auth::user()->name . ' | approved ' . data_get($log->request_payload, 'source', 'online terminal') . ' cancellation request | request id: ' . $log->request_id,
                'requested_host' => request()->ip(),
                'company_id' => Auth::user()->company_id,
            ]);

            return response()->json($response, 200);
        } catch (\Throwable $e) {
            Log::error('Online terminal approval exception: ' . $e->getMessage(), [
                'request_id' => $log->request_id,
                'source' => data_get($log->request_payload, 'source'),
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
                'transaction_id' => data_get($log->request_payload, 'transaction_id', $log->booking_reference),
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
            'message' => Auth::user()->name . ' | rejected ' . data_get($log->request_payload, 'source', 'online terminal') . ' cancellation request | request id: ' . $log->request_id,
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
            'transaction_id' => ['required_without:booking_reference', 'string', 'max:191'],
            'booking_reference' => ['nullable', 'string', 'max:191'],
            'seat_numbers' => ['required', 'array', 'min:1'],
            'seat_numbers.*' => ['required', 'string', 'max:20'],
            'cancellation_reason' => ['required', 'string', 'max:500'],
            'source' => ['required', 'string', 'max:100'],
            'deduction_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'refund_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    private function cancelSeatValidationMessages(Request $request)
    {
        return [
            'transaction_id.required_without' => 'The transaction id field is required.',
        ];
    }

    private function normalizeCancelSeatPayload(Request $request)
    {
        $invoiceId = $this->normalizeInvoiceId($request->invoice_id);
        $deductionPercentage = $this->resolveDeductionPercentage($request);

        return [
            'request_id' => trim((string) $request->request_id),
            'invoice_id' => $invoiceId,
            'seat_numbers' => $this->normalizeSeatNumbers($request->seat_numbers),
            'transaction_id' => $this->resolveRequestTransactionId($request, $invoiceId),
            'cancellation_reason' => trim((string) $request->cancellation_reason),
            'source' => trim((string) $request->source),
            'deduction_percentage' => $deductionPercentage,
            'refund_percentage' => $this->refundPercentageFromDeduction($deductionPercentage),
        ];
    }

    private function resolveDeductionPercentage(Request $request)
    {
        if ($request->filled('deduction_percentage')) {
            return $this->normalizePercentage($request->deduction_percentage);
        }

        if ($request->filled('refund_percentage')) {
            return $this->normalizePercentage($request->refund_percentage);
        }

        return $this->normalizePercentage(config('services.online_terminals.deduction_percentage', config('services.bookkaru.deduction_percentage', config('services.bookkaru.refund_percentage', 0))));
    }

    private function resolveLogDeductionPercentage(BookkaruApiLog $log)
    {
        return $this->normalizePercentage(data_get(
            $log->request_payload,
            'deduction_percentage',
            data_get($log->request_payload, 'refund_percentage', config('services.online_terminals.deduction_percentage', config('services.bookkaru.deduction_percentage', config('services.bookkaru.refund_percentage', 0))))
        ));
    }

    private function refundPercentageFromDeduction($deductionPercentage)
    {
        return $this->normalizePercentage(100 - $this->normalizePercentage($deductionPercentage));
    }

    private function calculatePercentageAmount($amount, $percentage)
    {
        return round(((float) $amount * $this->normalizePercentage($percentage)) / 100, 2);
    }

    private function normalizePercentage($percentage)
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
        $configuredKey = config('services.online_terminals.api_key', config('services.bookkaru.api_key'));
        $providedKey = $request->header('X-ONLINE-TERMINAL-API-KEY') ?: $request->header('X-BOOKKARU-API-KEY');

        if ($configuredKey && $providedKey && hash_equals($configuredKey, $providedKey)) {
            return true;
        }

        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return $this->isAllowedTerminalBookingRequest($request);
        }

        $accessTokenModel = Sanctum::personalAccessTokenModel();
        $accessToken = $accessTokenModel::findToken($bearerToken);

        if (!$accessToken || !$accessToken->tokenable) {
            return $this->isAllowedTerminalBookingRequest($request);
        }

        $allowedEmail = config('services.online_terminals.user_email', config('services.bookkaru.user_email'));

        if ($allowedEmail && strcasecmp($accessToken->tokenable->email, $allowedEmail) === 0) {
            return true;
        }

        return $this->isAllowedTerminalBookingRequest($request);
    }

    private function isAllowedTerminalBookingRequest(Request $request)
    {
        if (!$this->isAllowedTerminalSource($request->input('source'))) {
            return false;
        }

        $invoiceId = $this->normalizeInvoiceId($request->input('invoice_id'));
        if ($invoiceId === null) {
            return false;
        }

        $transactionIds = $this->requestTransactionIdCandidates($request);

        if (empty($transactionIds)) {
            return false;
        }

        return Ticket::withTrashed()
            ->where('invoice_id', $invoiceId)
            ->whereIn('transaction_id', $transactionIds)
            ->exists();
    }

    private function isAllowedTerminalSource($source)
    {
        $source = $this->normalizeSourceName($source);
        if ($source === '') {
            return false;
        }

        $allowedSources = config('services.online_terminals.allowed_sources', []);

        return collect($allowedSources)
            ->map(function ($allowedSource) {
                return $this->normalizeSourceName($allowedSource);
            })
            ->contains($source);
    }

    private function normalizeSourceName($source)
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', trim((string) $source)));
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

    private function normalizeTransactionId($transactionId)
    {
        $transactionId = trim((string) $transactionId);

        return $transactionId === '' ? null : $transactionId;
    }

    private function resolveRequestTransactionId(Request $request, $invoiceId)
    {
        $transactionIds = $this->requestTransactionIdCandidates($request);

        if ($invoiceId !== null && !empty($transactionIds)) {
            $matchingTransactionId = Ticket::withTrashed()
                ->where('invoice_id', $invoiceId)
                ->whereIn('transaction_id', $transactionIds)
                ->value('transaction_id');

            if ($matchingTransactionId) {
                return $matchingTransactionId;
            }
        }

        return $transactionIds[0] ?? null;
    }

    private function requestTransactionIdCandidates(Request $request)
    {
        return collect([
            $request->input('transaction_id'),
            $request->input('booking_reference'),
        ])->map(function ($transactionId) {
            return $this->normalizeTransactionId($transactionId);
        })->filter()->unique()->values()->all();
    }

    private function validateTicketsForRequest($invoiceId, array $seatNumbers, $transactionId = null)
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

        if ($transactionId !== null) {
            $mismatchedTransaction = $requestedTickets->contains(function ($ticket) use ($transactionId) {
                return trim((string) $ticket->transaction_id) !== trim((string) $transactionId);
            });

            if ($mismatchedTransaction) {
                return $this->businessError('Invalid transaction id.', 'INVALID_TRANSACTION_ID');
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

    private function findExistingSeatRequest($requestId, $invoiceId, $transactionId, array $seatNumbers)
    {
        return BookkaruApiLog::where('request_id', '!=', $requestId)
            ->where('normalized_invoice_id', $invoiceId)
            ->where('booking_reference', $transactionId)
            ->whereNotIn('status', ['failed', 'rejected', 'unauthorized'])
            ->get()
            ->first(function (BookkaruApiLog $log) use ($seatNumbers) {
                return count(array_intersect($seatNumbers, $log->seat_numbers ?? [])) > 0;
            });
    }

    private function buildPendingResponse($requestId, $invoiceId, array $seatNumbers, $transactionId, $source, $deductionPercentage, $refundPercentage, $refundReason)
    {
        if ($this->isAllowedTerminalSource($source)) {
            return $this->buildTerminalPendingResponse($requestId, $invoiceId, $seatNumbers, $transactionId, $deductionPercentage, $refundPercentage, $refundReason);
        }

        return $this->buildDefaultPendingResponse($requestId, $invoiceId, $seatNumbers, $transactionId, $source, $deductionPercentage, $refundPercentage);
    }

    private function buildDefaultPendingResponse($requestId, $invoiceId, array $seatNumbers, $transactionId, $source, $deductionPercentage, $refundPercentage)
    {
        return [
            'status' => 'success',
            'message' => 'Cancellation request received and is pending approval.',
            'data' => [
                'request_id' => $requestId,
                'invoice_id' => $invoiceId,
                'transaction_id' => $transactionId,
                'seat_numbers' => $seatNumbers,
                'deduction_percentage' => $deductionPercentage,
                'refund_percentage' => $refundPercentage,
                'cancellation_status' => 'pending',
                'source' => $source,
            ],
            'error' => null,
        ];
    }

    private function buildTerminalPendingResponse($requestId, $invoiceId, array $seatNumbers, $transactionId, $deductionPercentage, $refundPercentage, $refundReason)
    {
        $this->buildPendingRefundPreview($invoiceId, $seatNumbers, $transactionId, $deductionPercentage, $refundPercentage, $refundReason);

        return [
            'status' => 'success',
            'message' => 'Seat cancellation successful.',
            'data' => [
                'request_id' => $requestId,
                'invoice_id' => $invoiceId,
                'transaction_id' => $transactionId,
                'cancelled_seats' => $seatNumbers,
                'deduction_percentage' => $deductionPercentage,
            ],
            'error' => null,
        ];
    }

    private function buildPendingRefundPreview($invoiceId, array $seatNumbers, $transactionId, $deductionPercentage, $refundPercentage, $refundReason)
    {
        return Ticket::where('invoice_id', $invoiceId)
            ->whereIn('seat_no', $seatNumbers)
            ->get()
            ->map(function (Ticket $ticket) use ($transactionId, $deductionPercentage, $refundPercentage, $refundReason) {
                $seatFare = (float) $ticket->seat_fare;
                $deductionAmount = $this->calculatePercentageAmount($seatFare, $deductionPercentage);

                return [
                    'ticket_id' => $ticket->id,
                    'seat_no' => (string) $ticket->seat_no,
                    'transaction_id' => $transactionId,
                    'seat_fare' => $seatFare,
                    'deduction_percentage' => $deductionPercentage,
                    'deduction_amount' => $deductionAmount,
                    'refund_percentage' => $refundPercentage,
                    'refund_amount' => round($seatFare - $deductionAmount, 2),
                    'refund_reason' => $refundReason,
                ];
            })
            ->values()
            ->all();
    }

    private function buildResponseFromLog(BookkaruApiLog $log)
    {
        if ($log->current_status === 'pending') {
            return $this->buildPendingResponseFromLog($log);
        }

        if (is_array($log->response_payload) && !empty($log->response_payload)) {
            if ($this->isStoredPendingAcknowledgement($log->response_payload)) {
                return $this->buildPendingResponseFromLog($log);
            }

            return $this->normalizeStoredResponsePayload($log->response_payload);
        }

        $deductionPercentage = $this->resolveLogDeductionPercentage($log);
        $refundPercentage = $this->refundPercentageFromDeduction($deductionPercentage);

        return [
            'status' => $log->status !== 'failed' ? 'success' : 'error',
            'message' => $this->messageForStatus($log->current_status),
            'data' => [
                'request_id' => $log->request_id,
                'invoice_id' => $log->invoice_id,
                'transaction_id' => data_get($log->request_payload, 'transaction_id', $log->booking_reference),
                'seat_numbers' => $log->seat_numbers ?? [],
                'deduction_percentage' => $deductionPercentage,
                'refund_percentage' => $refundPercentage,
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

    private function isStoredPendingAcknowledgement(array $response)
    {
        return data_get($response, 'data.cancellation_status') === 'pending'
            || isset($response['data']['seat_numbers']);
    }

    private function buildPendingResponseFromLog(BookkaruApiLog $log)
    {
        $deductionPercentage = $this->resolveLogDeductionPercentage($log);
        $refundPercentage = $this->refundPercentageFromDeduction($deductionPercentage);

        return $this->buildPendingResponse(
            $log->request_id,
            $log->invoice_id,
            $log->seat_numbers ?? [],
            data_get($log->request_payload, 'transaction_id', $log->booking_reference),
            data_get($log->request_payload, 'source', 'Bookkaru'),
            $deductionPercentage,
            $refundPercentage,
            data_get($log->request_payload, 'cancellation_reason', 'Cancelled from online terminal')
        );
    }

    private function normalizeStoredResponsePayload(array $response)
    {
        if (!isset($response['data']) || !is_array($response['data'])) {
            return $response;
        }

        if (!isset($response['data']['transaction_id']) && isset($response['data']['booking_reference'])) {
            $response['data']['transaction_id'] = $response['data']['booking_reference'];
        }

        unset($response['data']['booking_reference']);

        if (($response['data']['cancellation_status'] ?? null) === 'pending') {
            $response['status'] = 'success';
            $response['message'] = 'Cancellation request received and is pending approval.';

            if (!isset($response['data']['seat_numbers']) && isset($response['data']['cancelled_seats'])) {
                $response['data']['seat_numbers'] = $response['data']['cancelled_seats'];
            }

            unset($response['data']['cancelled_seats'], $response['data']['approval_status'], $response['error']);
        }

        if (!isset($response['data']['deduction_percentage']) && isset($response['data']['refund_percentage'])) {
            $deductionPercentage = $this->normalizePercentage($response['data']['refund_percentage']);
            $response['data']['deduction_percentage'] = $deductionPercentage;
            $response['data']['refund_percentage'] = $this->refundPercentageFromDeduction($deductionPercentage);
        }

        return $response;
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
            'transaction_id' => data_get($log->request_payload, 'transaction_id', $log->booking_reference),
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
            'booking_reference' => $this->normalizeTransactionId($request->input('transaction_id', $request->input('booking_reference'))),
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

    private function storeFailureLog(Request $request, $requestId, $invoiceId, array $seatNumbers, $transactionId, $source, array $response, $status, $exceptionMessage = null)
    {
        if (!$requestId) {
            return;
        }

        BookkaruApiLog::create([
            'request_id' => $requestId,
            'invoice_id' => $invoiceId,
            'normalized_invoice_id' => $invoiceId,
            'booking_reference' => $transactionId,
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
