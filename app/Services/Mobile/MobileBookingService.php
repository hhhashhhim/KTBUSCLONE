<?php

namespace App\Services\Mobile;

use App\Models\ActivityLog;
use App\Models\Booking\TicketAdvancedBooked;
use App\Models\Booking\TicketIsPartial;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MobileAppConfig;
use App\Models\MobileBookingQuote;
use App\Models\MobilePayment;
use App\Models\PassengerAccount;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Terminal;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class MobileBookingService
{
    // Existing ERP/website ticket codes: male = 1, female = 0.
    private const TICKET_GENDERS = ['male' => 1, 'female' => 0];

    private $travel;
    private $loyalty;

    public function __construct(MobileTravelService $travel, MobileLoyaltyService $loyalty)
    {
        $this->travel = $travel;
        $this->loyalty = $loyalty;
    }

    public function paymentMethods(): array
    {
        $configured = config('mobile.payment_methods', []);
        if (Schema::hasTable('mobile_app_configs')) {
            $row = MobileAppConfig::where('company_id', $this->travel->companyId())->first();
            if ($row && is_array($row->payment_methods)) {
                $configured = $row->payment_methods;
            }
        }

        $available = ['counter'];
        if (config('mobile_payments.enabled') && Schema::hasTable('mobile_payments')
            && Schema::hasColumn('mobile_payments', 'environment')
            && (config('mobile_payments.preview_only') || app(MobilePaymentGateway::class)->checkoutHostAllowed())) {
            $gateway = app(MobilePaymentGateway::class);
            foreach (['jazzcash', 'bank_alfalah'] as $method) {
                if ($gateway->configured($method)) { $available[] = $method; }
            }
        }
        return array_values(array_unique(array_intersect((array) $configured, $available)));
    }

    private function walletEnabled(): bool
    {
        $enabled = (bool) config('mobile.features.wallet', false);
        if (!Schema::hasTable('mobile_app_configs')) {
            return $enabled;
        }

        $row = MobileAppConfig::where('company_id', $this->travel->companyId())->first();
        $features = (array) optional($row)->features;

        return array_key_exists('wallet', $features) ? (bool) $features['wallet'] : $enabled;
    }

    public function quote(PassengerAccount $account, array $input): MobileBookingQuote
    {
        $detail = $this->travel->findDetail(
            (int) $input['schedule_detail_id'],
            (int) $input['origin_id'],
            (int) $input['destination_id'],
            $input['date']
        );
        $layout = $this->travel->seatLayout(
            $detail->id,
            (int) $input['origin_id'],
            (int) $input['destination_id'],
            $input['date']
        );
        $this->assertSeatLimit($layout, count($input['seats']));
        $available = collect($layout['seats'])->where('status', 'available')->keyBy('number');
        $selected = collect($input['seats'])->map(function ($number) use ($available) {
            $seat = $available->get((string) $number);
            if (!$seat) {
                throw new RuntimeException('One or more selected seats are no longer available.', 409);
            }
            return $seat;
        })->values();

        $passengerSeats = collect($input['passengers'])->pluck('seat_number')->sort()->values();
        $selectedSeats = collect($input['seats'])->map(function ($number) {
            return (string) $number;
        })->sort()->values();
        if ($passengerSeats->all() !== $selectedSeats->all()) {
            throw new RuntimeException('A passenger is required for every selected seat.', 422);
        }

        $baseFare = $selected->sum(function ($seat) {
            return (float) $seat['price'];
        });
        $originalFare = collect($this->travel->faresForDetail(
            $detail,
            (int) $input['origin_id'],
            (int) $input['destination_id']
        ))->keyBy('class_id');
        $originalTotal = $selected->sum(function ($seat) use ($originalFare) {
            return (float) data_get($originalFare->get($seat['class_id']), 'original_amount', $seat['price']);
        });
        $wallet = $this->walletEnabled()
            ? $this->loyalty->deductionFor(
                $account,
                (int) ($input['points_to_use'] ?? 0),
                $baseFare
            )
            : ['points' => 0, 'amount' => 0.0];

        return MobileBookingQuote::create([
            'token' => Str::random(64),
            'passenger_account_id' => $account->id,
            'company_id' => $this->travel->companyId(),
            'schedule_detail_id' => $detail->id,
            'payload' => [
                'origin_id' => (int) $input['origin_id'],
                'destination_id' => (int) $input['destination_id'],
                'date' => $input['date'],
                'seats' => $selected->all(),
                'passengers' => array_values($input['passengers']),
                'wallet' => [
                    'points' => $wallet['points'],
                    'deduction' => $wallet['amount'],
                ],
            ],
            'base_fare' => $originalTotal,
            'discount' => max(0, $originalTotal - $baseFare),
            'taxes' => 0,
            'fees' => 0,
            'total' => max(0, $baseFare - $wallet['amount']),
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    public function create(
        PassengerAccount $account,
        string $quoteToken,
        string $paymentMethod
    ): array {
        app(MobilePaymentGateway::class)->assertTransactionsAllowed();
        if (!$account->mobile_verified_at) {
            throw new RuntimeException('Verify your mobile number before booking.', 403);
        }
        if (!in_array($paymentMethod, $this->paymentMethods(), true)) {
            throw new RuntimeException('The selected payment method is not available.', 422);
        }

        $systemUser = $this->bookingUser();
        $quote = MobileBookingQuote::query()
            ->where('token', $quoteToken)
            ->where('passenger_account_id', $account->id)
            ->first();
        if (!$quote) {
            throw new RuntimeException('The fare quote was not found.', 404);
        }
        if ($quote->used_at && Schema::hasTable('mobile_payments')) {
            $existing = MobilePayment::where('quote_id', $quote->id)->where('passenger_account_id', $account->id)
                ->where('company_id', $account->company_id)->where('method', $paymentMethod)->first();
            if ($existing) { return $this->presentInvoice($existing->invoice_id, $account); }
        }
        if ($paymentMethod !== 'counter' && (float) $quote->total <= 0) {
            throw new RuntimeException('Online payment requires a positive amount. Choose pay at branch for this reservation.', 422);
        }
        if ($quote->used_at || $quote->expires_at->isPast()) {
            throw new RuntimeException('This fare quote has expired. Request a new quote.', 409);
        }

        $payload = $quote->payload;
        $scheduleId = ScheduleDetail::whereKey($quote->schedule_detail_id)->value('schedule_id');
        if (!$scheduleId) {
            throw new RuntimeException('The selected schedule is no longer available.', 409);
        }
        // Share the lock used by the existing web/API booking paths so mobile
        // and counter sales cannot validate the same seat concurrently.
        $lock = Cache::lock('stayLock:' . $scheduleId, 15);
        if (!$lock->get()) {
            throw new RuntimeException('Seat availability is being updated. Please try again.', 409);
        }

        try {
            return DB::transaction(function () use ($account, $quote, $payload, $systemUser, $paymentMethod) {
                $quote->refresh();
                if ($quote->used_at || $quote->expires_at->isPast()) {
                    throw new RuntimeException('This fare quote has expired. Request a new quote.', 409);
                }

                $layout = $this->travel->seatLayout(
                    $quote->schedule_detail_id,
                    (int) $payload['origin_id'],
                    (int) $payload['destination_id'],
                    $payload['date']
                );
                $this->assertSeatLimit($layout, count($payload['seats']));
                $available = collect($layout['seats'])->where('status', 'available')->keyBy('number');
                foreach ($payload['seats'] as $quotedSeat) {
                    $current = $available->get((string) $quotedSeat['number']);
                    if (!$current || (float) $current['price'] !== (float) $quotedSeat['price']) {
                        throw new RuntimeException('Seat availability or fare changed. Request a new quote.', 409);
                    }
                }

                $detail = ScheduleDetail::with('schedule.route.fares')->find($quote->schedule_detail_id);
                if (!$detail) {
                    throw new RuntimeException('The selected schedule is no longer available.', 409);
                }
                $terminal = Terminal::query()
                    ->whereKey($this->travel->terminalId())
                    ->where('company_id', $this->travel->companyId())
                    ->first();
                if (!$terminal) {
                    throw new RuntimeException('The mobile booking terminal is not configured.', 503);
                }
                $wallet = (array) ($payload['wallet'] ?? []);
                $walletPoints = (int) ($wallet['points'] ?? 0);
                $walletDeduction = (float) ($wallet['deduction'] ?? 0);
                $walletCard = null;
                if ($walletPoints > 0) {
                    if (!$this->walletEnabled()) {
                        throw new RuntimeException('Loyalty wallet redemption is not currently available.', 409);
                    }

                    $walletCard = $this->loyalty->lockedCardFor($account);
                    if (!$walletCard) {
                        throw new RuntimeException('Your loyalty card is no longer active.', 409);
                    }
                    $fresh = $this->loyalty->deductionForCard(
                        $walletCard,
                        $walletPoints,
                        (float) $quote->total + $walletDeduction
                    );
                    if (abs($fresh['amount'] - $walletDeduction) > 0.01) {
                        throw new RuntimeException('Your loyalty wallet changed. Request a new quote.', 409);
                    }
                }
                $invoice = Invoice::create([
                    'schedule_id' => $detail->schedule_id,
                    'route_id' => optional($detail->schedule)->route_id,
                    'terminal_id' => $terminal->id,
                    'schedule_date' => $detail->schedule_date,
                    'schedule_time' => $detail->departure_time,
                    'company_id' => $this->travel->companyId(),
                    'passenger_account_id' => $account->id,
                    'added_by' => $systemUser->id,
                ]);
                $bookingNumber = ((int) Ticket::whereDate('date', $payload['date'])->max('booking_no')) + 1;
                $sequence = optional(optional($detail->schedule)->route)->fares ?: collect();
                $firstCity = optional($sequence->first())->departure_city_id;
                $lastCity = optional($sequence->last())->destination_city_id;
                $isPartial = (int) (
                    (int) $payload['origin_id'] !== (int) $firstCity
                    || (int) $payload['destination_id'] !== (int) $lastCity
                );
                $seatCount = count($payload['seats']);
                $remainingWalletDeduction = $walletDeduction;
                $remainingWalletPoints = $walletPoints;
                foreach ($payload['seats'] as $index => $quotedSeat) {
                    $passenger = collect($payload['passengers'])->firstWhere(
                        'seat_number',
                        (string) $quotedSeat['number']
                    );
                    $customer = Customer::firstOrNew([
                        'company_id' => $this->travel->companyId(),
                        'cnic' => $passenger['cnic'],
                    ]);
                    $customer->fill([
                        'name' => $passenger['full_name'],
                        'contact' => $passenger['mobile'],
                        'added_by' => $customer->exists ? $customer->added_by : $systemUser->id,
                        'updated_by' => $systemUser->id,
                    ])->save();
                    $isLastSeat = $index === $seatCount - 1;
                    $ticketWalletDeduction = $isLastSeat
                        ? $remainingWalletDeduction
                        : round($walletDeduction / $seatCount, 2);
                    $ticketWalletPoints = $isLastSeat
                        ? $remainingWalletPoints
                        : intdiv($walletPoints, $seatCount);
                    $remainingWalletDeduction -= $ticketWalletDeduction;
                    $remainingWalletPoints -= $ticketWalletPoints;
                    $ticket = Ticket::create([
                        'company_id' => $this->travel->companyId(),
                        'departure_city_id' => (int) $payload['origin_id'],
                        'destination_city_id' => (int) $payload['destination_id'],
                        'seat_no' => $quotedSeat['number'],
                        'bus_class_id' => $detail->bus_class_id,
                        'seat_fare' => $quotedSeat['price'],
                        'is_partial' => $isPartial,
                        'booking_no' => $bookingNumber,
                        'invoice_id' => $invoice->id,
                        'schedule_date' => $detail->schedule_date,
                        'schedule_time' => $detail->departure_time,
                        'schedule_time_exact' => $detail->departure_time,
                        'date' => $payload['date'],
                        'customer_id' => $customer->id,
                        'schedule_id' => $detail->schedule_id,
                        'route_id' => optional($detail->schedule)->route_id,
                        'schedule_details_id' => $detail->id,
                        'terminal_id' => $terminal->id,
                        'terminal_name' => $terminal->name,
                        'online_terminal' => $terminal->is_online_terminal,
                        'gender' => $this->ticketGender($passenger['gender']),
                        'type' => $paymentMethod === 'counter' ? 'advance booking' : 'pending booking',
                        'booked_time' => now(),
                        'added_by' => $systemUser->id,
                        'updated_by' => $systemUser->id,
                        'discount' => $ticketWalletDeduction,
                        'schedule_discount' => 0,
                        'terminal_discount' => 0,
                        'points_usage' => $ticketWalletPoints,
                    ]);

                    TicketAdvancedBooked::create([
                        'company_id' => $ticket->company_id,
                        'departure_city_id' => $ticket->departure_city_id,
                        'destination_city_id' => $ticket->destination_city_id,
                        'ticket_id' => $ticket->id,
                        'seat_no' => $ticket->seat_no,
                        'seat_fare' => $ticket->seat_fare,
                        'booking_no' => $ticket->booking_no,
                        'date' => $ticket->date,
                        'customer_id' => $ticket->customer_id,
                        'schedule_id' => $ticket->schedule_id,
                        'gender' => $ticket->gender,
                        'type' => $ticket->type,
                        'added_by' => $systemUser->id,
                    ]);

                    if ($isPartial) {
                        TicketIsPartial::create([
                            'company_id' => $ticket->company_id,
                            'departure_city_id' => $ticket->departure_city_id,
                            'destination_city_id' => $ticket->destination_city_id,
                            'ticket_id' => $ticket->id,
                            'seat_no' => $ticket->seat_no,
                            'seat_fare' => $ticket->seat_fare,
                            'booking_no' => $ticket->booking_no,
                            'date' => $ticket->date,
                            'customer_id' => $ticket->customer_id,
                            'schedule_id' => $ticket->schedule_id,
                            'gender' => $ticket->gender,
                            'type' => $ticket->type,
                            'added_by' => $systemUser->id,
                        ]);
                    }
                }

                if ($walletCard) {
                    $walletCard->decrement('starting_points', $walletPoints);
                }

                if ($paymentMethod !== 'counter') {
                    $expires = now()->addMinutes(max(1, min(30, (int) config('mobile_payments.checkout_minutes', 10))));
                    $departure = \Carbon\Carbon::parse($payload['date'] . ' ' . $detail->departure_time);
                    if ($departure->lte(now())) { throw new RuntimeException('This journey has already departed.', 409); }
                    if ($departure->lt($expires)) { $expires = $departure; }
                    MobilePayment::create([
                        'public_id' => (string) Str::uuid(), 'company_id' => $account->company_id,
                        'passenger_account_id' => $account->id, 'invoice_id' => $invoice->id, 'quote_id' => $quote->id,
                        'environment' => config('mobile_payments.environment'),
                        'method' => $paymentMethod, 'transaction_reference' => 'T' . now()->timezone('Asia/Karachi')->format('YmdHis') . random_int(10000, 99999),
                        'amount_minor' => (int) round((float) $quote->total * 100), 'currency' => 'PKR',
                        'status' => 'pending', 'expires_at' => $expires, 'ticket_count' => $seatCount,
                        'wallet_card_id' => optional($walletCard)->id, 'wallet_points' => $walletPoints,
                    ]);
                }
                $quote->update(['used_at' => now()]);
                ActivityLog::create([
                    'activity_by' => $systemUser->id,
                    'message' => 'Passenger mobile advance booking created | invoice id: ' . $invoice->id,
                    'requested_host' => request()->ip(),
                    'company_id' => $this->travel->companyId(),
                ]);

                return $this->presentInvoice($invoice->id, $account);
            });
        } finally {
            optional($lock)->release();
        }
    }

    public function listFor(PassengerAccount $account, int $page = 1, int $perPage = 20): array
    {
        $query = Invoice::query()
            ->where('company_id', $account->company_id)
            ->where('passenger_account_id', $account->id)
            ->latest('id');
        $total = $query->count();
        $invoiceIds = $query
            ->forPage($page, $perPage)
            ->pluck('id');

        return [
            'items' => $invoiceIds->map(function ($invoiceId) use ($account) {
                return $this->presentInvoice((int) $invoiceId, $account);
            })->all(),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }

    public function findFor(PassengerAccount $account, int $invoiceId): array
    {
        $owns = Invoice::query()
            ->where('company_id', $account->company_id)
            ->where('passenger_account_id', $account->id)
            ->whereKey($invoiceId)
            ->exists();
        if (!$owns) {
            throw new RuntimeException('The booking was not found.', 404);
        }

        return $this->presentInvoice($invoiceId, $account);
    }

    private function presentInvoice(int $invoiceId, PassengerAccount $account): array
    {
        $payment = Schema::hasTable('mobile_payments') ? MobilePayment::where('invoice_id', $invoiceId)
            ->where('company_id', $account->company_id)->where('passenger_account_id', $account->id)->first() : null;
        $tickets = Ticket::query()->when($payment, function ($query) { $query->withTrashed(); })
            ->with(['departure_city:id,name', 'destination_city:id,name', 'customer:id,name,cnic,contact'])
            ->where('company_id', $account->company_id)
            ->where('invoice_id', $invoiceId)
            ->orderBy('id')
            ->get();
        if ($tickets->isEmpty()) {
            throw new RuntimeException('The booking was not found.', 404);
        }
        $first = $tickets->first();
        $confirmed = $tickets->every(function ($ticket) {
            return $ticket->type === 'booked';
        });

        return [
            'id' => $invoiceId,
            'reference' => 'KT-' . str_pad((string) $invoiceId, 8, '0', STR_PAD_LEFT),
            'status' => $confirmed ? 'confirmed' : ($payment && in_array($payment->status, ['expired', 'review_required'], true) ? $payment->status : 'pending'),
            'payment_status' => $payment ? ($payment->paid_at ? 'paid' : $payment->status) : ($confirmed ? 'paid' : 'pending'),
            'payment' => $payment ? app(MobilePaymentService::class)->present($payment) : null,
            'origin_name' => optional($first->departure_city)->name,
            'destination_name' => optional($first->destination_city)->name,
            'departure_at' => $first->schedule_date . 'T' . $first->schedule_time,
            'seats' => $tickets->pluck('seat_no')->map(function ($seat) {
                return (string) $seat;
            })->values()->all(),
            'total' => (float) $tickets->sum(function ($ticket) {
                return (float) $ticket->seat_fare - (float) $ticket->discount;
            }),
            'qr_value' => $payment && (!$confirmed || $payment->status !== 'paid') ? null : 'KAINAT:' . $invoiceId,
            'passengers' => $tickets->map(function ($ticket) {
                return [
                    'full_name' => optional($ticket->customer)->name,
                    'cnic' => optional($ticket->customer)->cnic,
                    'mobile' => optional($ticket->customer)->contact,
                    'seat_number' => (string) $ticket->seat_no,
                    'gender' => array_flip(self::TICKET_GENDERS)[(string) $ticket->gender] ?? null,
                ];
            })->all(),
        ];
    }

    private function ticketGender(string $gender): int
    {
        // Validate persisted quotes too: quotes created before this fix may contain "other".
        if (!array_key_exists($gender, self::TICKET_GENDERS)) {
            throw new RuntimeException('This passenger gender is not supported for ticket booking. Please contact support.', 422);
        }

        return self::TICKET_GENDERS[$gender];
    }

    private function assertSeatLimit(array $layout, int $selectedCount): void
    {
        if ($selectedCount < 1 || $selectedCount > $layout['maximum_selectable_seats']) {
            throw new RuntimeException('The booking seat limit changed or the online seat quota is insufficient. Select fewer seats.', 409);
        }
    }

    private function bookingUser(): User
    {
        $userId = (int) config('mobile.booking_user_id');
        $user = User::query()
            ->whereKey($userId)
            ->where('company_id', $this->travel->companyId())
            ->first();
        if (!$user) {
            throw new RuntimeException('The mobile booking service user is not configured.', 503);
        }

        return $user;
    }
}
