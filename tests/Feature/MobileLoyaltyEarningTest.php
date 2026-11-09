<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\LoyaltyCard\CardAssign;
use App\Models\MobilePayment;
use App\Models\PassengerAccount;
use App\Models\Ticket;
use App\Services\Mobile\MobileLoyaltyEarningService;
use App\Services\Mobile\MobileLoyaltyService;
use App\Services\Mobile\MobilePaymentGateway;
use App\Services\Mobile\MobilePaymentService;
use App\Services\Mobile\MobileQuotaService;
use App\Services\TicketCancellationService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class MobileLoyaltyEarningTest extends TestCase
{
    private $payment;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.mobile_loyalty_testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
        config()->set('database.default', 'mobile_loyalty_testing');
        config()->set('cache.default', 'array');
        config()->set('mobile.booking_user_id', 20);
        config()->set('mobile_payments.environment', 'sandbox');
        // Earning does not depend on enabling the separate redemption switch.
        config()->set('mobile.features.wallet', false);
        Carbon::setTestNow(Carbon::parse('2026-11-08 10:00:00', 'Asia/Karachi'));
        $this->createSchema();
        PassengerAccount::create(['id' => 99, 'company_id' => 1, 'customer_id' => 500]);
        DB::table('customers')->insert([
            ['id' => 500, 'company_id' => 1, 'cnic' => 'booker'],
            ['id' => 600, 'company_id' => 1, 'cnic' => 'passenger'],
        ]);
        DB::table('card_categories')->insert([
            'id' => 1, 'company_id' => 1, 'name' => 'Gold', 'point_type' => 'flatPoints', 'point_flat' => 500,
        ]);
        CardAssign::create([
            'id' => 7, 'company_id' => 1, 'customer_id' => 500, 'cnic' => 'booker',
            'card_category_id' => 1, 'starting_points' => 10, 'expiry_date' => today()->addYear(),
        ]);
        DB::table('fare_tables')->insert([
            'id' => 1, 'company_id' => 1, 'from_city_id' => 1, 'to_city_id' => 2, 'distance_in_km' => 500,
        ]);
        Invoice::create(['id' => 1, 'company_id' => 1, 'schedule_id' => 40]);
        foreach ([1, 2] as $id) {
            Ticket::create([
                'id' => $id, 'company_id' => 1, 'invoice_id' => 1, 'customer_id' => 600,
                'departure_city_id' => 1, 'destination_city_id' => 2, 'schedule_id' => 40,
                'seat_fare' => 2750, 'discount' => 250, 'points_usage' => 0, 'type' => 'advance booking',
            ]);
        }
        $this->payment = MobilePayment::create([
            'public_id' => 'loyalty-test-payment', 'company_id' => 1, 'passenger_account_id' => 99,
            'invoice_id' => 1, 'quote_id' => 1, 'method' => 'bank_alfalah', 'transaction_reference' => 'test-only',
            'environment' => 'sandbox', 'amount_minor' => 500000, 'currency' => 'PKR',
            'ticket_count' => 2, 'status' => 'pending', 'expires_at' => now()->addMinutes(10), 'started_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('mobile_loyalty_testing');
        parent::tearDown();
    }

    public function test_verified_payment_earns_from_net_amount_once_and_wallet_reads_the_new_balance(): void
    {
        $service = $this->payments();
        $this->assertBalance(10);
        $this->assertSame('paid', $service->refresh($this->payment)->status);
        // 5500 gross - 500 discounts = 5000 paid; 500 rupees earns one point.
        $this->assertBalance(20);
        $this->assertSame(10, (int) DB::table('mobile_loyalty_awards')->sum('earned_points'));
        $this->assertDatabaseCount('mobile_loyalty_awards', 2);
        $this->assertDatabaseHas('mobile_loyalty_awards', ['ticket_id' => 1, 'earned_points' => 5, 'card_assign_id' => 7]);
        $service->refresh($this->payment->fresh());
        // A stale concurrent request still re-reads the locked payment state.
        $stale = $this->payment->fresh();
        $stale->status = 'pending';
        Carbon::setTestNow(now()->addSeconds(16));
        $service->refresh($stale);
        $this->assertBalance(20);
        $this->assertSame(1, DB::table('activity_logs')->where('message', 'like', 'Mobile loyalty earned%')->count());
        $wallet = app(MobileLoyaltyService::class)->wallet(PassengerAccount::find(99));
        $this->assertSame(20, $wallet['points_available']);
        $this->assertSame(2, Ticket::where('type', 'booked')->count());
    }

    public function test_distance_earning_is_once_per_booking_and_reverses_exactly_after_settings_change(): void
    {
        DB::table('card_categories')->update(['point_type' => 'distancePoints', 'point_distance' => 100]);
        $this->payments()->refresh($this->payment);
        $this->assertBalance(15); // 500 km / 100, not multiplied by two seats.
        $this->assertSame([3, 2], DB::table('mobile_loyalty_awards')->orderBy('ticket_id')
            ->pluck('earned_points')->map(function ($n) { return (int) $n; })->all());
        DB::table('card_categories')->update(['point_distance' => 1]);
        DB::table('fare_tables')->update(['distance_in_km' => 999]);
        $this->cancel(1);
        $this->assertBalance(12);
        $this->cancel(2);
        $this->assertBalance(10);
    }

    public function test_fractional_earnings_round_once_to_the_erp_whole_point_balance(): void
    {
        DB::table('card_categories')->update(['point_flat' => 2000]);
        $this->payments()->refresh($this->payment);
        $this->assertBalance(13); // 2.5 points rounds to 3, once for the booking.
        $this->assertSame(3, (int) DB::table('mobile_loyalty_awards')->sum('earned_points'));
    }

    /** @dataProvider ineligibleCards */
    public function test_ineligible_or_unconfigured_cards_do_not_earn_or_block_payment(string $case): void
    {
        switch ($case) {
            case 'no card': CardAssign::query()->delete(); break;
            case 'expired': CardAssign::query()->update(['expiry_date' => today()->subDay()]); break;
            case 'other company': CardAssign::query()->update(['company_id' => 2]); break;
            case 'other customer': CardAssign::query()->update(['customer_id' => 600]); break;
            case 'missing account': PassengerAccount::query()->delete(); break;
            case 'deleted category': DB::table('card_categories')->update(['deleted_at' => now()]); break;
            case 'foreign category': DB::table('card_categories')->update(['company_id' => 2]); break;
            case 'zero amount rate': DB::table('card_categories')->update(['point_flat' => 0]); break;
            case 'negative amount rate': DB::table('card_categories')->update(['point_flat' => -100]); break;
            case 'unknown type': DB::table('card_categories')->update(['point_type' => 'unknown']); break;
            case 'zero distance rate': DB::table('card_categories')->update(['point_type' => 'distancePoints', 'point_distance' => 0]); break;
            case 'missing distance':
                DB::table('card_categories')->update(['point_type' => 'distancePoints', 'point_distance' => 100]);
                DB::table('fare_tables')->update(['company_id' => 2]);
                break;
        }
        $this->assertSame('paid', $this->payments()->refresh($this->payment)->status);
        $this->assertSame(10, (int) CardAssign::withTrashed()->find(7)->starting_points);
        $this->assertSame(0, (int) DB::table('mobile_loyalty_awards')->sum('earned_points'));
        $this->cancel(1);
        $this->assertSame(10, (int) CardAssign::withTrashed()->find(7)->starting_points);
    }

    public static function ineligibleCards(): array
    {
        return array_map(function ($case) { return [$case]; }, [
            'no card', 'expired', 'other company', 'other customer', 'missing account', 'deleted category',
            'foreign category', 'zero amount rate', 'negative amount rate', 'unknown type',
            'zero distance rate', 'missing distance',
        ]);
    }

    /** @dataProvider unconfirmedPayments */
    public function test_unconfirmed_payments_never_earn_points(string $case, bool $paid, string $status): void
    {
        if ($case === 'expired' || $case === 'late receipt') {
            $this->payment->update(['expires_at' => now()->subSecond()]);
        }
        if ($case === 'amount changed') { Ticket::whereKey(1)->update(['discount' => 251]); }
        $service = $this->payments($paid, $case !== 'quota changed');
        $this->assertSame($status, $service->refresh($this->payment)->status);
        $this->assertBalance(10);
        $this->assertDatabaseCount('mobile_loyalty_awards', 0);
    }

    public static function unconfirmedPayments(): array
    {
        return [
            ['unpaid', false, 'pending'], ['expired', false, 'expired'],
            ['late receipt', true, 'review_required'], ['quota changed', true, 'review_required'],
            ['amount changed', true, 'review_required'],
        ];
    }

    public function test_cancellation_reverses_earned_and_returns_spent_points_to_original_cards_once(): void
    {
        $this->payment->update(['wallet_card_id' => 7, 'wallet_points' => 2]);
        Ticket::query()->update(['points_usage' => 1]);
        $this->payments()->refresh($this->payment);
        $this->assertBalance(20);
        // Even a deleted/expired card and changed category must reverse its recorded award.
        CardAssign::whereKey(7)->update(['expiry_date' => today()->subDay(), 'deleted_at' => now()]);
        DB::table('card_categories')->update(['point_flat' => 1]);
        CardAssign::create(['id' => 8, 'company_id' => 1, 'customer_id' => 600,
            'cnic' => 'passenger', 'card_category_id' => 1, 'starting_points' => 100, 'expiry_date' => today()->addYear()]);
        $staleTicket = Ticket::find(1);
        $this->cancel(1);
        $this->assertBalance(16); // 20 - 5 earned + 1 returned.
        $this->assertTrue(app(MobileLoyaltyEarningService::class)->reverseTicket($staleTicket));
        $this->assertBalance(16);
        $this->cancel(2);
        $this->assertBalance(12); // The balance before the original two-point redemption.
        $this->assertSame(100, (int) CardAssign::find(8)->starting_points);
        $this->assertSame(2, DB::table('mobile_loyalty_awards')->whereNotNull('reversed_at')->count());
    }

    public function test_cancelling_an_unpaid_or_pre_feature_mobile_ticket_does_not_subtract_unearned_points(): void
    {
        $this->payment->update(['wallet_card_id' => 7, 'wallet_points' => 2]);
        Ticket::query()->update(['points_usage' => 1]);
        $this->cancel(1);
        $this->assertBalance(11);
        Ticket::whereKey(2)->update(['type' => 'booked']);
        $this->cancel(2);
        $this->assertBalance(12);
    }

    public function test_rescheduled_ticket_cancellation_follows_the_erp_chain_without_recalculating_points(): void
    {
        $this->payment->update(['wallet_card_id' => 7, 'wallet_points' => 2]);
        Ticket::query()->update(['points_usage' => 1]);
        $this->payments()->refresh($this->payment);
        foreach ([[1, 3], [3, 4]] as [$oldId, $newId]) {
            $old = Ticket::find($oldId);
            $new = $old->replicate();
            $new->fill(['id' => $newId, 'seat_fare' => 9000, 'points_usage' => 0])->save();
            DB::table('ticket_reschedules')->insert(['company_id' => 1, 'old_ticket_id' => $oldId, 'new_ticket_id' => $newId]);
            $old->update(['type' => 'reschedule']);
            $old->delete();
        }
        $this->assertBalance(20);
        $this->cancel(4);
        $this->assertBalance(16); // Reverse original five earned, return original one spent.
        $this->assertDatabaseHas('mobile_loyalty_awards', ['ticket_id' => 1, 'earned_points' => 5, 'redeemed_points' => 1]);
        $this->assertNotNull(DB::table('mobile_loyalty_awards')->where('ticket_id', 1)->value('reversed_at'));
        $this->assertDatabaseCount('mobile_loyalty_awards', 2);
    }

    public function test_settlement_failure_rolls_back_earnings_and_ticket_confirmation_together(): void
    {
        DB::unprepared("CREATE TRIGGER fail_payment BEFORE UPDATE OF status ON mobile_payments
            WHEN NEW.status = 'paid' BEGIN SELECT RAISE(ABORT, 'Test settlement failure'); END");
        try {
            $this->payments()->refresh($this->payment);
            $this->fail('Expected simulated settlement failure.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('Test settlement failure', $exception->getMessage());
        }
        $this->assertBalance(10);
        $this->assertDatabaseCount('mobile_loyalty_awards', 0);
        $this->assertSame(2, Ticket::where('type', 'advance booking')->count());
        $this->assertSame('pending', $this->payment->fresh()->status);
    }

    public function test_missing_ledger_rolls_back_ticket_confirmation_and_allows_retry_after_migration(): void
    {
        (new \CreateMobileLoyaltyAwardsTable())->down();
        $service = $this->payments();
        try {
            $service->refresh($this->payment);
            $this->fail('Settlement must require the earning ledger.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(503, $exception->getCode());
        }
        $this->assertBalance(10);
        $this->assertSame('pending', $this->payment->fresh()->status);
        $this->assertSame(2, Ticket::where('type', 'advance booking')->count());
        (new \CreateMobileLoyaltyAwardsTable())->up();
        Carbon::setTestNow(now()->addSeconds(16));
        $this->assertSame('paid', $service->refresh($this->payment->fresh())->status);
        $this->assertBalance(20);
    }

    public function test_ledger_cannot_be_removed_after_awards_have_been_recorded(): void
    {
        $this->payments()->refresh($this->payment);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Reconcile mobile loyalty awards');
        (new \CreateMobileLoyaltyAwardsTable())->down();
    }

    private function payments(bool $paid = true, bool $quotaAvailable = true): MobilePaymentService
    {
        $gateway = Mockery::mock(MobilePaymentGateway::class);
        $gateway->shouldReceive('assertTransactionsAllowed')->andReturnNull();
        $gateway->shouldReceive('paid')->andReturn($paid);
        $quota = Mockery::mock(MobileQuotaService::class);
        $quota->shouldReceive('lockSchedule')->andReturnNull();
        if ($quotaAvailable) { $quota->shouldReceive('assertConfirmation')->andReturnNull(); }
        else { $quota->shouldReceive('assertConfirmation')->andThrow(new \RuntimeException('Quota changed', 409)); }
        $this->app->instance(MobileQuotaService::class, $quota);
        return new MobilePaymentService($gateway);
    }

    private function cancel(int $id): void
    {
        DB::transaction(function () use ($id) {
            $ticket = Ticket::whereKey($id)->lockForUpdate()->firstOrFail();
            app(TicketCancellationService::class)->cancelTicket($ticket, 100, 'Test cancellation', 20);
        });
    }

    private function assertBalance(int $points): void
    {
        $this->assertSame($points, (int) CardAssign::withTrashed()->find(7)->starting_points);
    }

    private function createSchema(): void
    {
        $tables = [
            'passenger_accounts' => ['customer_id', ''],
            'customers' => ['', 'cnic'],
            'invoices' => ['schedule_id', ''],
            'tickets' => ['invoice_id customer_id departure_city_id destination_city_id schedule_id seat_fare discount points_usage updated_by refund_percentage refund_amount', 'type transaction_id booked_time refund_reason'],
            'fare_tables' => ['from_city_id to_city_id distance_in_km', ''],
            'ticket_is_partials' => ['ticket_id', 'type'],
            'ticket_e_l_t_s' => ['ticket_id', ''],
            'ticket_reschedules' => ['old_ticket_id new_ticket_id', ''],
            'booking_cancels' => ['ticket_id percentage added_by', 'type reason'],
            'activity_logs' => ['activity_by', 'message requested_host'],
        ];
        foreach ($tables as $name => [$numbers, $strings]) {
            Schema::create($name, function (Blueprint $table) use ($numbers, $strings) {
                $table->id(); $table->integer('company_id')->nullable();
                foreach (array_filter(explode(' ', $numbers)) as $column) { $table->integer($column)->nullable(); }
                foreach (array_filter(explode(' ', $strings)) as $column) { $table->text($column)->nullable(); }
                $table->timestamps(); $table->softDeletes();
            });
        }
        foreach ([
            '2023_03_06_145458_create_card_categories_table.php' => 'CreateCardCategoriesTable',
            '2023_03_10_174419_create_card_assigns_table.php' => 'CreateCardAssignsTable',
            '2026_09_12_000008_create_mobile_payments_table.php' => 'CreateMobilePaymentsTable',
            '2026_09_12_000009_add_environment_to_mobile_payments.php' => 'AddEnvironmentToMobilePayments',
            '2026_11_08_000001_create_mobile_loyalty_awards_table.php' => 'CreateMobileLoyaltyAwardsTable',
        ] as $file => $class) {
            require_once database_path('migrations/' . $file);
            (new $class())->up();
        }
    }
}
