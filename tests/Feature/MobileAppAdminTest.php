<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\MobileAppPermissions;
use Carbon\Carbon;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAppAdminTest extends TestCase
{
    private const BASE = '/api/web/v1/mobile-app';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost', 'database.default' => 'mobile_app_admin_test',
            'database.connections.mobile_app_admin_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'cache.default' => 'array', 'session.driver' => 'array', 'services.terminal_cancellations.approval_enabled' => false]);
        $this->app['url']->forceRootUrl('http://localhost');
        DB::purge('mobile_app_admin_test');
        Schema::clearResolvedInstance('db.schema');
        Carbon::setTestNow(Carbon::parse('2026-10-20 10:00:00', 'Asia/Karachi'));
        $this->schema();
        $this->fixtures();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function table(string $name, array $columns): void
    {
        Schema::create($name, function (Blueprint $table) use ($columns) {
            $table->id();
            foreach ($columns as $column) { $table->text($column)->nullable(); }
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function schema(): void
    {
        $this->table('companies', ['name', 'modules']);
        $this->table('roles', ['name', 'company_id', 'permissions']);
        $this->table('users', ['name', 'email', 'company_id', 'terminal_id', 'role_id', 'route_ids', 'hide', 'is_super_admin']);
        $this->table('terminals', ['name', 'company_id', 'hide', 'cancellation_allowed', 'is_online_terminal']);
        $this->table('routes', ['name', 'company_id', 'hide']);
        $this->table('customers', ['name', 'company_id', 'contact', 'cnic']);
        $this->table('passenger_accounts', ['customer_id', 'company_id', 'mobile', 'email', 'password', 'gender', 'mobile_verified_at',
            'otp_digest', 'password_reset_otp_digest', 'password_reset_token_digest', 'remember_token']);
        $this->table('invoices', ['company_id', 'passenger_account_id', 'terminal_id', 'route_id', 'schedule_id', 'schedule_date', 'schedule_time', 'added_by']);
        $this->table('tickets', ['company_id', 'terminal_id', 'route_id', 'invoice_id', 'customer_id', 'seat_no', 'type', 'seat_fare', 'discount',
            'schedule_id', 'schedule_details_id', 'schedule_date', 'schedule_time', 'schedule_time_exact', 'date', 'ticket_closing_id',
            'is_partial', 'points_usage', 'refund_amount', 'refund_reason', 'refund_percentage', 'departure_city_id', 'destination_city_id', 'added_by', 'updated_by']);
        $this->table('mobile_payments', ['company_id', 'passenger_account_id', 'invoice_id', 'quote_id', 'public_id', 'method', 'transaction_reference',
            'status', 'amount_minor', 'currency', 'ticket_count', 'paid_at', 'expires_at', 'started_at', 'checked_at', 'environment']);
        $this->table('booking_cancels', ['company_id', 'ticket_id', 'percentage', 'reason', 'type', 'added_by']);
        $this->table('ticket_e_l_t_s', ['ticket_id']);
        $this->table('card_assigns', ['company_id', 'cnic', 'card_category_id', 'starting_points']);
        $this->table('activity_logs', ['company_id', 'activity_by', 'message', 'requested_host']);
        $this->table('schedules', ['company_id', 'route_id']);
        $this->table('schedule_details', ['company_id', 'schedule_id', 'schedule_date', 'departure_time']);
        $this->table('ticket_closings', ['company_id', 'terminal_id', 'schedule_id', 'schedule_date', 'closed']);
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id(); $table->string('tokenable_type'); $table->integer('tokenable_id'); $table->string('name');
            $table->string('token'); $table->text('abilities')->nullable(); $table->timestamp('last_used_at')->nullable(); $table->timestamps();
        });
        require_once database_path('migrations/2026_10_20_000001_create_mobile_app_admin_tables.php');
        (new \CreateMobileAppAdminTables())->up();
        require_once database_path('migrations/2026_10_20_000004_add_mobile_user_controls.php');
        (new \AddMobileUserControls())->up();
    }

    private function fixtures(): void
    {
        $modules = json_encode(MobileAppPermissions::append([], true));
        DB::table('companies')->insert([['id' => 1, 'name' => 'Kainat', 'modules' => $modules], ['id' => 2, 'name' => 'Other', 'modules' => $modules]]);
        DB::table('terminals')->insert([
            ['id' => 10, 'name' => 'Lahore', 'company_id' => 1, 'hide' => 0, 'cancellation_allowed' => 1, 'is_online_terminal' => 0],
            ['id' => 11, 'name' => 'Karachi', 'company_id' => 1, 'hide' => 0, 'cancellation_allowed' => 1, 'is_online_terminal' => 0],
            ['id' => 20, 'name' => 'Other company', 'company_id' => 2, 'hide' => 0, 'cancellation_allowed' => 1, 'is_online_terminal' => 0],
        ]);
        DB::table('routes')->insert([
            ['id' => 3, 'name' => 'Lahore to Karachi', 'company_id' => 1, 'hide' => 0],
            ['id' => 4, 'name' => 'Multan to Lahore', 'company_id' => 1, 'hide' => 0],
            ['id' => 8, 'name' => 'Other route', 'company_id' => 2, 'hide' => 0],
        ]);
        for ($id = 1; $id <= 5; $id++) {
            $company = $id === 4 ? 2 : 1;
            DB::table('customers')->insert(['id' => $id, 'company_id' => $company, 'name' => 'Passenger ' . $id, 'contact' => '0300123456' . $id, 'cnic' => '352021234567' . $id]);
            DB::table('passenger_accounts')->insert(['id' => $id, 'customer_id' => $id, 'company_id' => $company,
                'mobile' => '0300123456' . $id, 'email' => 'passenger' . $id . '@example.test', 'password' => 'never-expose-password',
                'otp_digest' => 'never-expose-otp', 'password_reset_token_digest' => 'never-expose-reset', 'remember_token' => 'never-expose-remember',
                'created_at' => '2026-10-19 12:00:00', 'mobile_verified_at' => '2026-10-19 12:00:00']);
        }
        foreach ([[100, 1, 1, 10, 3], [101, 2, 1, 11, 3], [102, 3, 1, 10, 4], [103, 4, 2, 20, 8], [104, null, 1, 10, 3]] as [$id, $account, $company, $terminal, $route]) {
            DB::table('invoices')->insert(['id' => $id, 'company_id' => $company, 'passenger_account_id' => $account,
                'terminal_id' => $terminal, 'route_id' => $route, 'schedule_id' => 77, 'schedule_date' => '2026-10-21',
                'schedule_time' => '14:00:00', 'created_at' => '2026-10-20 09:00:00']);
        }
        $this->ticket(1, 100, 1, 10, 3, 1);
        $this->ticket(2, 100, 1, 10, 3, 1);
        $this->ticket(3, 101, 1, 11, 3, 2);
        $this->ticket(4, 102, 1, 10, 4, 3);
        $this->ticket(5, 103, 2, 20, 8, 4);
        $this->ticket(6, 104, 1, 10, 3, 1);
        DB::table('schedules')->insert(['id' => 77, 'company_id' => 1, 'route_id' => 3]);
        DB::table('schedule_details')->insert(['id' => 77, 'company_id' => 1, 'schedule_id' => 77, 'schedule_date' => '2026-10-21', 'departure_time' => '14:00:00']);
    }

    private function ticket(int $id, int $invoice, int $company, int $terminal, int $route, int $customer): void
    {
        DB::table('tickets')->insert(['id' => $id, 'company_id' => $company, 'terminal_id' => $terminal, 'route_id' => $route,
            'invoice_id' => $invoice, 'customer_id' => $customer, 'seat_no' => (string) $id, 'type' => 'advance booking',
            'seat_fare' => 2000, 'discount' => 200, 'points_usage' => 0, 'schedule_id' => 77, 'schedule_details_id' => 77,
            'schedule_date' => '2026-10-21', 'schedule_time' => '14:00:00', 'date' => '2026-10-21', 'is_partial' => 0,
            'created_at' => '2026-10-20 09:00:00']);
    }

    private function login(array $denied = [], bool $global = true, string $routes = 'all'): User
    {
        $permissions = MobileAppPermissions::append([], true);
        foreach ($permissions[0]['buttons'] as &$button) {
            if (in_array($button['name'], $denied, true) || (!$global && $button['name'] === 'mobile-app-all-terminals')) { $button['allow'] = false; }
        }
        unset($button);
        DB::table('roles')->updateOrInsert(['id' => 1], ['company_id' => 1, 'name' => 'Operator', 'permissions' => json_encode($permissions)]);
        $user = User::updateOrCreate(['id' => 1], ['company_id' => 1, 'terminal_id' => 10, 'role_id' => 1, 'name' => 'Mobile operator', 'hide' => 0, 'route_ids' => $routes]);
        $user->unsetRelations();
        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    private function payment(int $invoice = 100, string $status = 'pending', array $attributes = []): void
    {
        $row = DB::table('invoices')->where('id', $invoice)->first();
        DB::table('mobile_payments')->insert(array_merge(['id' => $invoice, 'company_id' => $row->company_id,
            'passenger_account_id' => $row->passenger_account_id, 'invoice_id' => $invoice, 'quote_id' => $invoice,
            'public_id' => 'payment-' . $invoice, 'method' => 'bank_alfalah', 'transaction_reference' => 'REF-' . $invoice,
            'status' => $status, 'amount_minor' => 360000, 'currency' => 'PKR', 'ticket_count' => 2,
            'environment' => 'live', 'expires_at' => '2026-10-20 10:10:00', 'created_at' => '2026-10-20 09:00:00'], $attributes));
    }

    private function removal(array $attributes = [])
    {
        return $this->postJson(self::BASE . '/bookings/100/remove-seats', array_merge([
            'ticket_ids' => [1], 'reason' => 'Passenger requested reservation removal', 'refund_percentage' => 0,
        ], $attributes));
    }

    public function test_staff_authentication_and_module_parent_are_required(): void
    {
        $this->getJson(self::BASE . '/options')->assertUnauthorized();
        $this->actingAs(new GenericUser(['id' => 900, 'company_id' => 1]), 'sanctum');
        $this->getJson(self::BASE . '/options')->assertForbidden();
        $this->login();
        $permissions = MobileAppPermissions::append([], true);
        $permissions[0]['allow'] = false;
        DB::table('roles')->where('id', 1)->update(['permissions' => json_encode($permissions)]);
        auth('sanctum')->user()->unsetRelations();
        $this->getJson(self::BASE . '/options')->assertForbidden();
    }

    public function test_options_and_read_endpoints_enforce_detailed_action_privileges(): void
    {
        $this->login();
        foreach (['options', 'overview', 'users', 'bookings', 'payments', 'reports?report=sales', 'activity'] as $endpoint) {
            $this->getJson(self::BASE . '/' . $endpoint)->assertOk();
        }
        $this->login(['mobile-app-view-dashboard', 'mobile-app-view-users', 'mobile-app-view-bookings', 'mobile-app-view-payments', 'mobile-app-view-reports', 'mobile-app-view-activity']);
        foreach (['overview', 'users', 'bookings', 'payments', 'reports?report=sales', 'activity'] as $endpoint) {
            $this->getJson(self::BASE . '/' . $endpoint)->assertForbidden();
        }
    }

    public function test_scoped_lists_exclude_other_terminals_routes_companies_and_nonmobile_bookings(): void
    {
        $this->payment(100);
        $this->payment(101);
        $this->payment(102);
        $this->payment(103);
        $this->login([], false, '[3]');
        $this->assertSame([1], array_map('intval', array_column($this->getJson(self::BASE . '/users')->assertOk()->json('data.items'), 'id')));
        $this->assertSame([100], array_map('intval', array_column($this->getJson(self::BASE . '/bookings')->assertOk()->json('data.items'), 'id')));
        $this->assertSame([100], array_map('intval', array_column($this->getJson(self::BASE . '/payments')->assertOk()->json('data.items'), 'invoice_id')));
        $this->getJson(self::BASE . '/users/2')->assertNotFound();
        $this->getJson(self::BASE . '/bookings/101')->assertNotFound();
        $this->getJson(self::BASE . '/bookings/102')->assertNotFound();
        $this->getJson(self::BASE . '/bookings/103')->assertNotFound();
        $this->getJson(self::BASE . '/bookings/104')->assertNotFound();
        $this->getJson(self::BASE . '/bookings?terminal_id=11')->assertForbidden();
    }

    public function test_global_access_stays_company_scoped_and_masks_passenger_private_fields(): void
    {
        $this->login(['mobile-app-view-sensitive-users']);
        $users = $this->getJson(self::BASE . '/users')->assertOk();
        $ids = array_map('intval', array_column($users->json('data.items'), 'id'));
        sort($ids);
        $this->assertSame([1, 2, 3, 5], $ids);
        $detail = $this->getJson(self::BASE . '/users/1')->assertOk();
        $this->assertSame('*******4561', $detail->json('data.user.mobile'));
        $this->assertSame('***@example.test', $detail->json('data.user.email'));
        $this->assertNull($detail->json('data.user.cnic'));
        foreach (['password', 'otp_digest', 'password_reset_token_digest', 'remember_token'] as $secret) {
            $this->assertArrayNotHasKey($secret, $detail->json('data.user'));
        }
        $this->assertStringNotContainsString('never-expose', $detail->getContent());
        $booking = $this->getJson(self::BASE . '/bookings/100')->assertOk();
        $this->assertSame('*******4561', $booking->json('data.passenger_mobile'));
        $this->assertCount(2, $booking->json('data.tickets'));
        $this->getJson(self::BASE . '/users/4')->assertNotFound();
        $this->login();
        $this->getJson(self::BASE . '/users/1')->assertOk()->assertJsonPath('data.user.mobile', '03001234561');
    }

    public function test_missing_mobile_tables_report_unavailable_without_breaking_options(): void
    {
        Schema::drop('passenger_accounts');
        $this->login();
        $this->getJson(self::BASE . '/options')->assertOk();
        $this->getJson(self::BASE . '/users')->assertStatus(503);
        $this->getJson(self::BASE . '/bookings')->assertStatus(503);
        $this->getJson(self::BASE . '/payments')->assertStatus(503);
    }

    public function test_user_status_change_requires_reason_and_expected_state_and_is_audited(): void
    {
        $this->login();
        $this->putJson(self::BASE . '/users/1/status', ['status' => 'blocked', 'expected_status' => 'active'])->assertUnprocessable();
        $this->putJson(self::BASE . '/users/1/status', ['status' => 'blocked', 'reason' => 'Documented support decision', 'expected_status' => 'blocked'])->assertStatus(409);
        $this->assertSame(0, DB::table('mobile_app_passenger_controls')->count());
        $this->putJson(self::BASE . '/users/1/status', ['status' => 'blocked', 'reason' => 'Documented support decision', 'expected_status' => 'active'])->assertOk();
        $this->assertDatabaseHas('mobile_app_passenger_controls', ['company_id' => 1, 'passenger_account_id' => 1, 'status' => 'blocked']);
        $this->assertDatabaseHas('mobile_app_admin_audits', ['company_id' => 1, 'actor_id' => 1, 'reason' => 'Documented support decision']);
        $this->putJson(self::BASE . '/users/1/status', ['status' => 'active', 'reason' => 'Reviewed by supervisor', 'expected_status' => 'active'])->assertStatus(409);
        $this->assertDatabaseHas('mobile_app_passenger_controls', ['passenger_account_id' => 1, 'status' => 'blocked']);
    }

    public function test_user_controls_reject_missing_privilege_scoped_staff_and_foreign_company(): void
    {
        $payload = ['status' => 'blocked', 'reason' => 'Review requested', 'expected_status' => 'active'];
        $this->login(['mobile-app-manage-users', 'mobile-app-revoke-sessions']);
        $this->putJson(self::BASE . '/users/1/status', $payload)->assertForbidden();
        $this->postJson(self::BASE . '/users/1/revoke-sessions', ['reason' => 'Review requested'])->assertForbidden();
        $this->login([], false, '[3]');
        $this->putJson(self::BASE . '/users/1/status', $payload)->assertForbidden();
        $this->login();
        $this->putJson(self::BASE . '/users/4/status', $payload)->assertNotFound();
        $this->assertSame(0, DB::table('mobile_app_passenger_controls')->count());
    }

    public function test_all_assigned_current_company_routes_enable_global_controls_without_widening_booking_history(): void
    {
        DB::table('routes')->insert(['id' => 9, 'company_id' => 1, 'name' => 'Old route', 'deleted_at' => '2026-10-19 10:00:00']);
        DB::table('invoices')->insert(['id' => 105, 'company_id' => 1, 'passenger_account_id' => 1,
            'terminal_id' => 10, 'route_id' => 9, 'schedule_id' => 77, 'schedule_date' => '2026-10-21',
            'schedule_time' => '14:00:00', 'created_at' => '2026-10-20 09:00:00']);
        $this->ticket(7, 105, 1, 10, 9, 1);
        $this->login([], true, '[3,4]');
        $this->getJson(self::BASE . '/options')->assertOk()->assertJsonPath('data.permissions.users_update', true)
            ->assertJsonPath('data.permissions.settings_update', true)->assertJsonPath('data.permissions.content_update', true);
        // An unbooked passenger is companywide data; complete current route coverage permits managing it.
        $this->getJson(self::BASE . '/users/5')->assertOk();
        $this->putJson(self::BASE . '/users/5/status', ['status' => 'blocked', 'reason' => 'Companywide support review', 'expected_status' => 'active'])->assertOk();
        $this->getJson(self::BASE . '/bookings/105')->assertNotFound();
        $this->getJson(self::BASE . '/bookings')->assertOk()->assertJsonCount(3, 'data.items');
    }

    public function test_foreign_routes_cannot_replace_missing_company_routes_and_new_routes_revoke_global_controls(): void
    {
        $payload = ['status' => 'blocked', 'reason' => 'Companywide support review', 'expected_status' => 'active'];
        $this->login([], true, '[3,8]');
        $this->getJson(self::BASE . '/options')->assertOk()->assertJsonPath('data.permissions.users_update', false);
        $this->putJson(self::BASE . '/users/5/status', $payload)->assertForbidden();
        $this->login([], true, '[3,4]');
        $this->getJson(self::BASE . '/options')->assertOk()->assertJsonPath('data.permissions.users_update', true);
        DB::table('routes')->insert(['id' => 9, 'company_id' => 1, 'name' => 'New company route']);
        $this->getJson(self::BASE . '/options')->assertOk()->assertJsonPath('data.permissions.users_update', false);
        $this->putJson(self::BASE . '/users/5/status', $payload)->assertForbidden();
        $this->assertSame(0, DB::table('mobile_app_passenger_controls')->count());
    }

    public function test_empty_or_invalid_route_assignments_and_empty_company_route_set_fail_closed(): void
    {
        foreach (['[]', '{}', 'invalid', '[true,null,"invalid"]'] as $assigned) {
            $this->login([], true, $assigned);
            $this->getJson(self::BASE . '/options')->assertOk()->assertJsonPath('data.permissions.users_update', false);
        }
        DB::table('routes')->where('company_id', 1)->update(['deleted_at' => '2026-10-20 09:00:00']);
        $this->login([], true, '[3,4,8]');
        $this->getJson(self::BASE . '/options')->assertOk()->assertJsonPath('data.permissions.users_update', false);
        $this->putJson(self::BASE . '/users/5/status', ['status' => 'blocked', 'reason' => 'Companywide support review', 'expected_status' => 'active'])->assertForbidden();
    }

    public function test_revoke_sessions_removes_only_selected_passenger_tokens_and_audits_reason(): void
    {
        $this->login();
        foreach ([[1, 'App\\Models\\PassengerAccount', 1], [2, 'App\\Models\\PassengerAccount', 2], [3, 'App\\Models\\User', 1]] as [$id, $type, $owner]) {
            DB::table('personal_access_tokens')->insert(['id' => $id, 'tokenable_type' => $type, 'tokenable_id' => $owner, 'name' => 'test', 'token' => hash('sha256', (string) $id)]);
        }
        $this->postJson(self::BASE . '/users/1/revoke-sessions', [])->assertUnprocessable();
        $this->postJson(self::BASE . '/users/1/revoke-sessions', ['reason' => 'Passenger lost their device'])->assertOk();
        $this->assertSame([2, 3], DB::table('personal_access_tokens')->orderBy('id')->pluck('id')->all());
        $this->assertDatabaseHas('mobile_app_admin_audits', ['company_id' => 1, 'actor_id' => 1, 'reason' => 'Passenger lost their device']);
    }

    public function test_selected_reserved_seat_is_soft_cancelled_with_history_and_other_seats_preserved(): void
    {
        $this->login(['mobile-app-refund-seats']);
        $this->removal()->assertOk();
        $removed = DB::table('tickets')->where('id', 1)->first();
        $this->assertNotNull($removed->deleted_at);
        $this->assertSame('canceled', $removed->type);
        $this->assertDatabaseHas('booking_cancels', ['company_id' => 1, 'ticket_id' => 1, 'type' => 'advance booking', 'added_by' => 1]);
        $this->assertNull(DB::table('tickets')->where('id', 2)->value('deleted_at'));
        $this->assertNull(DB::table('tickets')->where('id', 6)->value('deleted_at'));
        $this->assertDatabaseHas('mobile_app_admin_audits', ['company_id' => 1, 'terminal_id' => 10, 'route_id' => 3, 'actor_id' => 1]);
        $this->removal()->assertStatus(409);
        $this->assertSame(1, DB::table('booking_cancels')->count());
    }

    public function test_seat_removal_validates_selected_ids_reason_and_module_privilege(): void
    {
        $this->login(['mobile-app-remove-seats']);
        $this->removal()->assertForbidden();
        $this->login();
        $this->removal(['reason' => ''])->assertUnprocessable();
        $this->removal(['ticket_ids' => []])->assertUnprocessable();
        $this->removal(['ticket_ids' => [1, 1]])->assertUnprocessable();
        $response = $this->removal(['ticket_ids' => [3]]);
        $this->assertContains($response->status(), [404, 409, 422]);
        $this->assertSame(0, DB::table('booking_cancels')->count());
        $this->assertNull(DB::table('tickets')->where('id', 1)->value('deleted_at'));
    }

    public function test_confirmed_seat_requires_financial_privilege_and_uses_discounted_fare(): void
    {
        DB::table('tickets')->where('id', 1)->update(['type' => 'booked']);
        $this->login(['mobile-app-refund-seats']);
        $this->removal(['refund_percentage' => 25])->assertForbidden();
        $this->login();
        $this->removal(['refund_percentage' => 25])->assertOk();
        $ticket = DB::table('tickets')->where('id', 1)->first();
        $this->assertEquals(450, $ticket->refund_amount);
        $this->assertEquals(25, $ticket->refund_percentage);
        $this->assertDatabaseHas('booking_cancels', ['ticket_id' => 1, 'type' => 'booked', 'percentage' => 75]);
    }

    public function test_pending_or_review_gateway_payment_prevents_seat_removal(): void
    {
        $this->payment(100, 'pending');
        $this->login();
        $this->removal()->assertStatus(409);
        DB::table('mobile_payments')->where('invoice_id', 100)->update(['status' => 'review_required']);
        $this->removal()->assertStatus(409);
        $this->assertSame(0, DB::table('booking_cancels')->count());
    }

    public function test_online_booking_rejects_partial_removal_but_accepts_full_cancellation(): void
    {
        $this->login();
        DB::table('tickets')->where('invoice_id', 100)->update(['type' => 'booked']);
        $this->payment(100, 'paid', ['paid_at' => '2026-10-20 09:15:00']);
        $this->getJson(self::BASE . '/bookings/100')->assertOk()->assertJsonPath('data.seat_removal_policy.requires_full_cancellation', true);
        $this->removal(['ticket_ids' => [1], 'refund_percentage' => 25])->assertStatus(409);
        $this->assertSame(0, DB::table('booking_cancels')->count());
        $this->assertSame(2, DB::table('tickets')->where('invoice_id', 100)->whereNull('deleted_at')->count());
        $this->removal(['ticket_ids' => [1, 2], 'refund_percentage' => 25])->assertOk();
        $this->assertSame(0, DB::table('tickets')->where('invoice_id', 100)->whereNull('deleted_at')->count());
        $this->assertSame(2, DB::table('booking_cancels')->count());
        $this->assertEquals(900, DB::table('tickets')->where('invoice_id', 100)->sum('refund_amount'));
    }

    public function test_last_no_payment_seat_must_remain_until_passenger_history_supports_full_cancellation(): void
    {
        $this->login();
        $this->getJson(self::BASE . '/bookings/100')->assertOk()->assertJsonPath('data.seat_removal_policy.preserve_one_seat', true);
        $this->removal(['ticket_ids' => [1, 2]])->assertStatus(409);
        $this->assertSame(2, DB::table('tickets')->where('invoice_id', 100)->whereNull('deleted_at')->count());
        $this->assertSame(0, DB::table('booking_cancels')->count());
        $this->assertSame(0, DB::table('mobile_app_admin_audits')->count());
        $this->removal(['ticket_ids' => [1]])->assertOk();
        $this->assertSame(1, DB::table('tickets')->where('invoice_id', 100)->whereNull('deleted_at')->count());
        $this->removal(['ticket_ids' => [2]])->assertStatus(409);
        $this->assertNull(DB::table('tickets')->where('id', 2)->value('deleted_at'));
        $this->assertSame(1, DB::table('booking_cancels')->count());
        $this->assertNull(DB::table('invoices')->where('id', 100)->value('deleted_at'));
    }

    public function test_departed_and_closed_tickets_are_not_removed(): void
    {
        $this->login();
        DB::table('tickets')->where('id', 1)->update(['schedule_date' => '2026-10-19']);
        $this->removal()->assertStatus(409);
        DB::table('tickets')->where('id', 1)->update(['schedule_date' => '2026-10-21', 'ticket_closing_id' => 123]);
        $this->removal()->assertStatus(409);
        $this->assertSame(0, DB::table('booking_cancels')->count());
    }

    public function test_journey_segment_cancellation_preserves_another_ticket_for_the_same_seat(): void
    {
        $this->login();
        DB::table('tickets')->where('id', 1)->update(['is_partial' => 1]);
        DB::table('tickets')->where('id', 2)->update(['is_partial' => 1, 'seat_no' => '1']);
        $this->removal()->assertOk();
        $this->assertNotNull(DB::table('tickets')->where('id', 1)->value('deleted_at'));
        $this->assertNull(DB::table('tickets')->where('id', 2)->value('deleted_at'));
        $this->assertSame(1, DB::table('booking_cancels')->count());
    }

    public function test_overview_and_all_report_types_use_the_permitted_terminal_and_routes(): void
    {
        $this->payment(100);
        $this->payment(101);
        $this->payment(102);
        $this->login([], false, '[3]');
        $overview = $this->getJson(self::BASE . '/overview')->assertOk();
        $this->assertSame(1, $overview->json('data.metrics.users'));
        $this->assertSame(1, $overview->json('data.metrics.total_bookings'));
        $this->assertSame(2, $overview->json('data.metrics.active_seats'));
        $this->assertCount(1, $overview->json('data.recent_bookings'));
        foreach (['sales', 'routes', 'passengers'] as $report) {
            $response = $this->getJson(self::BASE . '/reports?report=' . $report)->assertOk();
            $this->assertEquals(1, $response->json('data.summary.bookings'));
        }
        $this->getJson(self::BASE . '/reports?report=cancellations')->assertOk()->assertJsonCount(0, 'data.items');
        $this->getJson(self::BASE . '/reports?report=payments')->assertOk()->assertJsonCount(1, 'data.items');
    }

    public function test_deleted_mobile_account_does_not_hide_preserved_bookings_payments_or_sales(): void
    {
        $this->payment(100);
        $this->login();
        DB::table('passenger_accounts')->where('id', 1)->delete();
        $this->getJson(self::BASE . '/bookings/100')->assertOk();
        $payments = $this->getJson(self::BASE . '/payments')->assertOk()->json('data.items');
        $this->assertSame([100], array_map('intval', array_column($payments, 'invoice_id')));
        $report = $this->getJson(self::BASE . '/reports?report=sales')->assertOk();
        $this->assertEquals(3, $report->json('data.summary.bookings'));
    }

    public function test_sensitive_contact_privilege_also_requires_user_view_privilege(): void
    {
        $this->login(['mobile-app-view-users']);
        $this->getJson(self::BASE . '/users')->assertForbidden();
        $booking = $this->getJson(self::BASE . '/bookings/100')->assertOk();
        $this->assertSame('*******4561', $booking->json('data.passenger_mobile'));
        $options = $this->getJson(self::BASE . '/options')->assertOk();
        $this->assertFalse($options->json('data.permissions.users_sensitive'));
    }

    public function test_report_export_requires_both_privileges_dates_and_escapes_spreadsheet_formulas(): void
    {
        $query = '/reports/export?report=passengers&from=2026-10-20&to=2026-10-20';
        $this->login(['mobile-app-export-reports']);
        $this->getJson(self::BASE . $query)->assertForbidden();
        $this->login(['mobile-app-view-reports']);
        $this->getJson(self::BASE . $query)->assertForbidden();
        $this->login();
        $this->getJson(self::BASE . '/reports/export?report=passengers')->assertUnprocessable();
        DB::table('customers')->where('id', 1)->update(['name' => '=DANGEROUS_FORMULA()']);
        $response = $this->get(self::BASE . $query)->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=DANGEROUS_FORMULA()", $csv);
        $this->assertStringNotContainsString('never-expose', $csv);
        $this->assertDatabaseHas('mobile_app_admin_audits', ['company_id' => 1, 'actor_id' => 1, 'action' => 'report_exported']);
    }

    public function test_reschedule_history_does_not_inflate_seats_cancellations_or_payment_exceptions(): void
    {
        $this->login();
        DB::table('tickets')->where('id', 1)->update(['type' => 'reschedule', 'deleted_at' => '2026-10-20 09:30:00']);
        DB::table('tickets')->where('id', 2)->update(['type' => 'booked']);
        $this->ticket(7, 100, 1, 10, 3, 1);
        DB::table('tickets')->where('id', 7)->update(['type' => 'booked']);
        $this->payment(100, 'paid', ['paid_at' => '2026-10-20 09:15:00']);
        $booking = $this->getJson(self::BASE . '/bookings/100')->assertOk();
        $this->assertSame('confirmed', $booking->json('data.status'));
        $this->assertSame(2, $booking->json('data.seat_count'));
        $this->assertSame(0, $booking->json('data.cancelled_seats'));
        $payments = $this->getJson(self::BASE . '/payments')->assertOk()->json('data.items');
        $this->assertCount(1, $payments);
        $this->assertFalse($payments[0]['requires_review']);
    }

    public function test_booking_dates_and_payment_dates_remain_independent(): void
    {
        $this->login();
        DB::table('invoices')->where('id', 100)->update(['created_at' => '2026-10-19 23:59:00']);
        // A payment can be created after its booking, including across a date boundary.
        $this->payment(100, 'paid', ['paid_at' => '2026-10-20 09:15:00']);
        DB::table('tickets')->where('invoice_id', 100)->update(['type' => 'booked']);
        $dates = '?from=2026-10-20&to=2026-10-20';
        $bookings = $this->getJson(self::BASE . '/bookings' . $dates)->assertOk()->json('data.items');
        $this->assertSame([102, 101], array_map('intval', array_column($bookings, 'invoice_id')));
        $this->getJson(self::BASE . '/payments' . $dates)->assertOk()->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.invoice_id', '100');
        $overview = $this->getJson(self::BASE . '/overview' . $dates)->assertOk();
        $overview->assertJsonPath('data.metrics.total_bookings', 2)->assertJsonPath('data.metrics.confirmed_sales', 0);
    }

    public function test_sandbox_sales_are_excluded_by_default_and_available_with_explicit_environment(): void
    {
        $this->login();
        DB::table('tickets')->where('invoice_id', 100)->update(['type' => 'booked']);
        $this->payment(100, 'paid', ['environment' => 'sandbox', 'paid_at' => '2026-10-20 09:15:00']);
        $this->payment(101, 'pending', ['environment' => null]);

        $overview = $this->getJson(self::BASE . '/overview')->assertOk();
        $this->assertSame(2, $overview->json('data.metrics.total_bookings'));
        $this->assertEquals(0, $overview->json('data.metrics.confirmed_sales'));
        $sales = $this->getJson(self::BASE . '/reports?report=sales')->assertOk();
        $this->assertEquals(0, $sales->json('data.summary.confirmed_sales'));
        $this->assertEquals(2, $sales->json('data.summary.bookings'));
        $payments = $this->getJson(self::BASE . '/payments')->assertOk()->json('data.items');
        $this->assertSame([101], array_map('intval', array_column($payments, 'invoice_id')));
        $defaultBookings = $this->getJson(self::BASE . '/bookings')->assertOk()->json('data.items');
        $this->assertNotContains(100, array_map('intval', array_column($defaultBookings, 'invoice_id')));

        $sandbox = $this->getJson(self::BASE . '/overview?environment=sandbox')->assertOk();
        $this->assertSame(1, $sandbox->json('data.metrics.total_bookings'));
        $this->assertEquals(3600, $sandbox->json('data.metrics.confirmed_sales'));
        $sandboxSales = $this->getJson(self::BASE . '/reports?report=sales&environment=sandbox')->assertOk();
        $this->assertEquals(3600, $sandboxSales->json('data.summary.confirmed_sales'));
        $this->assertEquals(1, $sandboxSales->json('data.summary.bookings'));
        $sandboxPayments = $this->getJson(self::BASE . '/payments?environment=sandbox')->assertOk()->json('data.items');
        $this->assertSame([100], array_map('intval', array_column($sandboxPayments, 'invoice_id')));
        $all = $this->getJson(self::BASE . '/overview?environment=all')->assertOk();
        $this->assertSame(3, $all->json('data.metrics.total_bookings'));
        $this->assertEquals(3600, $all->json('data.metrics.confirmed_sales'));
        $this->getJson(self::BASE . '/payments?environment=all')->assertOk()->assertJsonCount(2, 'data.items');
        $this->getJson(self::BASE . '/bookings/100')->assertOk()->assertJsonPath('data.payment.environment', 'sandbox');
    }

    public function test_confirmed_cancellation_preserves_existing_approval_control(): void
    {
        $this->login();
        config(['services.terminal_cancellations.approval_enabled' => true]);
        DB::table('tickets')->where('id', 1)->update(['type' => 'booked']);
        $this->removal()->assertStatus(409);
        $this->assertSame(0, DB::table('booking_cancels')->count());
        $this->assertNull(DB::table('tickets')->where('id', 1)->value('deleted_at'));
    }

    public function test_activity_includes_global_rows_only_with_complete_scope_and_respects_explicit_filters(): void
    {
        DB::table('routes')->insert(['id' => 9, 'company_id' => 1, 'name' => 'Old route', 'deleted_at' => '2026-10-19 10:00:00']);
        foreach ([[1, 1, 10, 3], [2, 1, 11, 4], [3, 1, null, null], [4, 1, 10, 9], [5, 2, null, null], [6, 1, 10, null]] as [$id, $company, $terminal, $route]) {
            DB::table('mobile_app_admin_audits')->insert(['id' => $id, 'company_id' => $company, 'terminal_id' => $terminal,
                'route_id' => $route, 'actor_id' => 1, 'action' => 'test', 'subject_type' => 'booking', 'subject_id' => '100',
                'created_at' => '2026-10-20 09:00:00']);
        }
        $ids = function ($query = '') { return array_map('intval', array_column($this->getJson(self::BASE . '/activity' . $query)->assertOk()->json('data.items'), 'id')); };
        $this->login([], true, '[3,4]');
        $this->assertSame([6, 3, 2, 1], $ids());
        $this->assertSame([1], $ids('?route_id=3'));
        $this->assertSame([6, 1], $ids('?terminal_id=10'));
        $this->assertSame([], $ids('?route_id=9'));
        $this->login([], true, '[3]');
        $this->assertSame([1], $ids());
        $this->login([], false, 'all');
        $this->assertSame([4, 1], $ids());
    }

    public function test_activity_hides_global_and_other_boundary_audits_from_scoped_staff(): void
    {
        $this->login([], false, '[3]');
        foreach ([[1, 1, 10, 3], [2, 1, 11, 3], [3, 1, 10, 4], [4, 1, null, null], [5, 2, 20, 8]] as [$id, $company, $terminal, $route]) {
            DB::table('mobile_app_admin_audits')->insert(['id' => $id, 'company_id' => $company, 'terminal_id' => $terminal,
                'route_id' => $route, 'actor_id' => 1, 'action' => 'test', 'subject_type' => 'booking', 'subject_id' => '100',
                'created_at' => '2026-10-20 09:00:00']);
        }
        $rows = $this->getJson(self::BASE . '/activity')->assertOk()->json('data.items');
        $this->assertSame([1], array_map('intval', array_column($rows, 'id')));
    }

    private function limitsBody(int $id = 1, array $changes = []): array
    {
        $controls = $this->getJson(self::BASE . '/users/' . $id)->assertOk()->json('data.controls');
        return ['limits' => array_replace($controls['limits'], $changes), 'revision' => $controls['revision'], 'reason' => 'Support reviewed passenger limits'];
    }

    public function test_blacklist_requires_its_own_privilege_and_preserves_hashed_identities(): void
    {
        $body = ['status' => 'blacklisted', 'expected_status' => 'active', 'reason' => 'Repeated abusive reservations'];
        $this->login(['mobile-app-blacklist-users']);
        $this->putJson(self::BASE . '/users/1/status', $body)->assertForbidden();
        $this->login(['mobile-app-manage-users']);
        $this->putJson(self::BASE . '/users/1/status', $body)->assertOk();
        $this->assertDatabaseCount('mobile_app_blacklist_entries', 2);
        $this->assertDatabaseHas('mobile_app_blacklist_entries', ['company_id' => 1, 'passenger_account_id' => 1, 'kind' => 'mobile',
            'identity_hash' => \App\Services\MobileAppControlData::identity('mobile', '+92 300 1234561')]);
        $this->getJson(self::BASE . '/users?status=blacklisted')->assertOk()->assertJsonPath('data.items.0.status', 'blacklisted');
        $this->login(['mobile-app-blacklist-users']);
        $body = ['status' => 'active', 'expected_status' => 'blacklisted', 'reason' => 'Restriction reviewed'];
        $this->putJson(self::BASE . '/users/1/status', $body)->assertForbidden();
        $this->login();
        $this->putJson(self::BASE . '/users/1/status', $body)->assertOk();
        $this->assertDatabaseCount('mobile_app_blacklist_entries', 0);
    }

    public function test_limits_require_dedicated_scope_validate_zero_and_reject_stale_edits(): void
    {
        $this->login(); $body = $this->limitsBody(1, ['daily_seat_limit' => 0, 'monthly_seat_limit' => 5, 'otp_daily_limit' => 2]);
        $this->login(['mobile-app-manage-user-limits']);
        $this->putJson(self::BASE . '/users/1/limits', $body)->assertForbidden();
        $this->login([], false);
        $this->putJson(self::BASE . '/users/1/limits', $body)->assertForbidden();
        $this->getJson(self::BASE . '/users/1')->assertOk()->assertJsonPath('data.controls', null);
        $this->login();
        $this->putJson(self::BASE . '/users/4/limits', $body)->assertNotFound();
        $this->putJson(self::BASE . '/users/1/limits', $body)->assertOk()->assertJsonPath('data.limits.daily_seat_limit', 0)
            ->assertJsonPath('data.limits.otp_monthly_limit', null)->assertJsonPath('data.usage.daily', 2);
        $this->putJson(self::BASE . '/users/1/limits', $body)->assertStatus(409);
        $this->assertDatabaseHas('mobile_app_admin_audits', ['action' => 'passenger_limits_changed', 'actor_id' => 1]);
        $body = $this->limitsBody(1, ['daily_seat_limit' => -1]);
        $this->putJson(self::BASE . '/users/1/limits', $body)->assertUnprocessable();
        $body = $this->limitsBody(1, ['is_admin' => true]);
        $this->putJson(self::BASE . '/users/1/limits', $body)->assertUnprocessable();
    }

    public function test_forced_reset_revokes_sessions_and_codes_without_changing_or_exposing_password(): void
    {
        $this->login();
        DB::table('personal_access_tokens')->insert(['tokenable_type' => 'App\\Models\\PassengerAccount', 'tokenable_id' => 1, 'name' => 'mobile', 'token' => 'fixture-token']);
        $body = ['revision' => $this->limitsBody()['revision'], 'reason' => 'Account recovery requested'];
        $this->login(['mobile-app-force-password-reset']);
        $this->postJson(self::BASE . '/users/1/require-password-reset', $body)->assertForbidden();
        $this->login();
        $response = $this->postJson(self::BASE . '/users/1/require-password-reset', $body)->assertOk()->assertJsonPath('data.sessions_revoked', 1);
        $this->assertDatabaseHas('mobile_app_passenger_controls', ['passenger_account_id' => 1, 'password_reset_required' => 1]);
        $this->assertDatabaseHas('passenger_accounts', ['id' => 1, 'password' => 'never-expose-password', 'otp_digest' => null, 'password_reset_token_digest' => null]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertStringNotContainsString('never-expose', $response->getContent());
        $audit = DB::table('mobile_app_admin_audits')->where('action', 'password_reset_required')->first();
        $this->assertNotNull($audit); $this->assertStringNotContainsString('never-expose', $audit->changes);
        $this->postJson(self::BASE . '/users/1/require-password-reset', $body)->assertStatus(409);
    }

    public function test_usage_includes_cancelled_legacy_seats_and_never_double_counts_ledger(): void
    {
        $this->login();
        DB::table('tickets')->where('invoice_id', 100)->update(['type' => 'canceled', 'deleted_at' => now()]);
        $this->getJson(self::BASE . '/users/1')->assertOk()->assertJsonPath('data.controls.usage.daily', 2);
        DB::table('mobile_app_seat_usage')->insert(['company_id' => 1, 'passenger_account_id' => 1, 'invoice_id' => 100, 'seats' => 2, 'booked_at' => '2026-10-20 09:00:00']);
        $this->getJson(self::BASE . '/users/1')->assertOk()->assertJsonPath('data.controls.usage.daily', 2);
    }

    private function scopedCompanyAdmin(): User
    {
        $user = $this->login([], true, '[3]');
        DB::table('roles')->where('id', $user->role_id)->update(['name' => 'Admin']);
        $user->unsetRelations();
        return $user;
    }

    public function test_admin_configuration_flags_are_enabled_without_expanding_passenger_or_booking_scope(): void
    {
        $user = $this->scopedCompanyAdmin();
        $options = $this->getJson(self::BASE . '/options')->assertOk()
            ->assertJsonPath('data.permissions.settings_update', true)
            ->assertJsonPath('data.permissions.app_controls_update', true)
            ->assertJsonPath('data.permissions.content_update', true)
            ->assertJsonPath('data.permissions.users_update', true)
            ->assertJsonPath('data.permissions.users_limits', true);
        $this->assertSame('[3]', $user->route_ids);
        $this->getJson(self::BASE . '/users/3')->assertNotFound();
        $this->getJson(self::BASE . '/users/4')->assertNotFound();
        $this->getJson(self::BASE . '/bookings/102')->assertNotFound();
        $this->getJson(self::BASE . '/bookings/103')->assertNotFound();
        $this->putJson(self::BASE . '/users/3/status', ['status' => 'blocked', 'expected_status' => 'active', 'reason' => 'Review required'])->assertNotFound();
        foreach ($options->json('data.notices') as $notice) {
            if (is_array($notice)) { $this->assertNotContains('mobile-app-settings', $notice['screens']); }
        }
    }

    public function test_scoped_admin_can_read_configuration_and_visible_passenger_audits_only(): void
    {
        $user = $this->scopedCompanyAdmin();
        foreach ([[1, 1, 'app_config', null, null], [2, 1, 'app_policy', null, null], [3, 1, 'fleet_media', null, null],
            [4, 1, 'passenger', null, null], [5, 2, 'app_policy', null, null], [6, 1, 'booking', 10, 4], [7, 1, 'booking', 10, 3]] as [$id, $company, $type, $terminal, $route]) {
            DB::table('mobile_app_admin_audits')->insert(['id' => $id, 'company_id' => $company, 'actor_id' => 1,
                'subject_type' => $type, 'subject_id' => '1', 'action' => 'test', 'terminal_id' => $terminal, 'route_id' => $route,
                'created_at' => '2026-10-20 09:00:00']);
        }
        DB::table('mobile_app_admin_audits')->insert(['id' => 8, 'company_id' => 1, 'actor_id' => 1,
            'subject_type' => 'passenger', 'subject_id' => '3', 'action' => 'test', 'created_at' => '2026-10-20 09:00:00']);
        $ids = function ($query = '') { return array_map('intval', array_column($this->getJson(self::BASE . '/activity' . $query)->assertOk()->json('data.items'), 'id')); };
        $this->assertSame([7, 4, 3, 2, 1], $ids());
        $this->assertSame([7], $ids('?route_id=3'));
        $this->assertSame([7], $ids('?terminal_id=10'));
        $user->forceFill(['route_ids' => 'all'])->save();
        $this->assertSame([8, 7, 6, 4, 3, 2, 1], $ids());
        $this->assertSame([7], $ids('?route_id=3'));
        $this->assertSame([7, 6], $ids('?terminal_id=10'));
    }
    public function test_full_cnic_search_and_display_follow_sensitive_access_and_scope(): void
    {
        DB::table('customers')->where('id', 1)->update(['cnic' => '35202-1234567-1']);
        $this->login([], false, '[3]');
        $this->getJson(self::BASE . '/users?search=35202-1234567-1')->assertOk()->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.cnic', '3520212345671');
        $this->getJson(self::BASE . '/users?search=3520212345671')->assertOk()->assertJsonCount(1, 'data.items');
        $this->getJson(self::BASE . '/users?search=3520212345673')->assertOk()->assertJsonCount(0, 'data.items');
        $this->login(['mobile-app-view-sensitive-users']);
        $this->getJson(self::BASE . '/users?search=3520212345671')->assertOk()->assertJsonCount(0, 'data.items');
        $this->getJson(self::BASE . '/users/1')->assertOk()->assertJsonPath('data.user.cnic', null)
            ->assertJsonPath('data.user.cnic_masked', '*********5671');
    }

    public function test_registration_report_counts_current_status_across_all_pages_and_registration_dates(): void
    {
        $this->login();
        DB::table('passenger_accounts')->where('id', 2)->update(['mobile_verified_at' => null]);
        DB::table('passenger_accounts')->where('id', 5)->update(['created_at' => '2026-10-18 12:00:00']);
        DB::table('mobile_app_passenger_controls')->insert(['company_id' => 1, 'passenger_account_id' => 3, 'status' => 'blocked']);
        $response = $this->getJson(self::BASE . '/reports?report=user_registrations&from=2026-10-18&to=2026-10-19&per_page=1')->assertOk();
        $this->assertEquals(4, $response->json('data.summary.users'));
        $this->assertEquals(3, $response->json('data.summary.verified_users'));
        $this->assertEquals(1, $response->json('data.summary.unverified_users'));
        $this->assertEquals(1, $response->json('data.summary.blocked_users'));
        $this->assertEquals(2, $response->json('data.pagination.total'));
        $response->assertJsonCount(1, 'data.items')->assertJsonPath('data.items.0.date', '2026-10-19');
        $this->getJson(self::BASE . '/reports?report=user_registrations&from=2026-10-20&to=2026-10-20')->assertOk()
            ->assertJsonCount(0, 'data.items')->assertJsonPath('data.summary.users', 0);
    }

    public function test_user_verification_filters_and_export_use_the_same_identity_masking(): void
    {
        DB::table('passenger_accounts')->where('id', 1)->update(['mobile_verified_at' => null]);
        DB::table('customers')->where('id', 1)->update(['name' => '=FORMULA()']);
        $this->login(['mobile-app-view-sensitive-users']);
        $query = '?report=user_verification&from=2026-10-19&to=2026-10-19&verification=unverified';
        $response = $this->getJson(self::BASE . '/reports' . $query)->assertOk()->assertJsonCount(1, 'data.items');
        $response->assertJsonPath('data.items.0.cnic', '*********5671')->assertJsonPath('data.items.0.mobile', '*******4561')
            ->assertJsonPath('data.items.0.email', '***@example.test')->assertJsonPath('data.items.0.verification', 'unverified');
        $csv = $this->get(self::BASE . '/reports/export' . $query)->assertOk()->streamedContent();
        $this->assertStringContainsString('*********5671', $csv);
        $this->assertStringNotContainsString('35202-1234567-1', $csv);
        $this->assertStringNotContainsString('03001234561', $csv);
        $this->assertStringContainsString("'=FORMULA()", $csv);
        $this->assertStringNotContainsString('never-expose', $csv);
        $this->login();
        $this->getJson(self::BASE . '/reports' . $query)->assertOk()->assertJsonPath('data.items.0.cnic', '35202-1234567-1');
        $csv = $this->get(self::BASE . '/reports/export' . $query)->assertOk()->streamedContent();
        $this->assertStringContainsString('35202-1234567-1', $csv);
        $this->assertDatabaseHas('mobile_app_admin_audits', ['subject_id' => 'user_verification', 'action' => 'report_exported']);
    }

    public function test_current_restriction_report_uses_control_update_dates_and_excludes_released_accounts(): void
    {
        $this->login();
        foreach ([[1, 1, 'blocked', '2026-10-20'], [2, 1, 'blacklisted', '2026-10-19'],
            [3, 1, 'active', '2026-10-20'], [4, 2, 'blacklisted', '2026-10-20']] as [$account, $company, $status, $date]) {
            DB::table('mobile_app_passenger_controls')->insert(['company_id' => $company, 'passenger_account_id' => $account,
                'status' => $status, 'updated_at' => $date . ' 09:00:00']);
        }
        $response = $this->getJson(self::BASE . '/reports?report=user_restrictions&from=2026-10-20&to=2026-10-20')->assertOk()->assertJsonCount(1, 'data.items');
        $this->assertEquals(1, $response->json('data.items.0.id'));
        $response->assertJsonPath('data.items.0.status', 'blocked');
        $all = $this->getJson(self::BASE . '/reports?report=user_restrictions&from=2026-10-19&to=2026-10-20')->assertOk();
        $this->assertEquals(2, $all->json('data.summary.users'));
        $this->assertEquals(1, $all->json('data.summary.blacklisted_users'));
    }

    public function test_user_booking_reports_include_older_and_zero_booking_accounts_without_inflating_ticket_counts(): void
    {
        $this->login();
        DB::table('invoices')->insert(['id' => 105, 'company_id' => 1, 'passenger_account_id' => 1, 'terminal_id' => 10,
            'route_id' => 3, 'created_at' => '2026-10-20 09:30:00']);
        $this->ticket(7, 105, 1, 10, 3, 1);
        DB::table('tickets')->where('id', 7)->update(['type' => 'booked']);
        DB::table('tickets')->where('id', 1)->update(['type' => 'canceled', 'deleted_at' => now()]);
        $this->payment(102, 'pending', ['environment' => 'sandbox']);
        $query = '?report=user_bookings&from=2026-10-20&to=2026-10-20&per_page=1';
        $response = $this->getJson(self::BASE . '/reports' . $query)->assertOk()->assertJsonCount(1, 'data.items');
        $this->assertEquals(4, $response->json('data.summary.users'));
        $this->assertEquals(3, $response->json('data.summary.bookings'));
        $this->assertEquals(2, $response->json('data.summary.users_without_bookings'));
        $this->assertEquals(1, $response->json('data.summary.repeat_bookers'));
        $this->assertEquals(1800, $response->json('data.summary.confirmed_sales'));
        $this->assertEquals(1, $response->json('data.items.0.id'));
        $this->assertEquals(2, $response->json('data.items.0.bookings'));
        $this->assertEquals(3, $response->json('data.items.0.seats'));
        $this->assertEquals(1, $response->json('data.items.0.cancelled_seats'));
        $sandbox = $this->getJson(self::BASE . '/reports' . $query . '&environment=sandbox')->assertOk();
        $this->assertEquals(1, $sandbox->json('data.summary.bookings'));
        $this->assertEquals(3, $sandbox->json('data.items.0.id'));
        $this->getJson(self::BASE . '/reports' . $query . '&search=Passenger%201&status=active')->assertOk()
            ->assertJsonPath('data.items.0.status', 'active')->assertJsonPath('data.pagination.total', 1);
    }

    public function test_no_booking_report_is_period_based_and_excludes_future_accounts(): void
    {
        $this->login();
        $response = $this->getJson(self::BASE . '/reports?report=users_without_bookings&from=2026-10-20&to=2026-10-20')->assertOk();
        $this->assertSame([5], array_map('intval', array_column($response->json('data.items'), 'id')));
        // An account with earlier booking history can still have no bookings in this period.
        $this->getJson(self::BASE . '/reports?report=users_without_bookings&from=2026-10-21&to=2026-10-21')->assertOk()
            ->assertJsonPath('data.pagination.total', 4);
        DB::table('passenger_accounts')->where('id', 5)->update(['created_at' => '2026-10-21 00:00:00']);
        $this->getJson(self::BASE . '/reports?report=users_without_bookings&from=2026-10-20&to=2026-10-20')->assertOk()
            ->assertJsonCount(0, 'data.items');
    }

    public function test_all_user_reports_enforce_account_scope_and_cannot_be_used_without_user_access(): void
    {
        $types = \App\Services\MobileAppUserReports::TYPES;
        $this->login([], false, '[3]');
        DB::table('mobile_app_passenger_controls')->insert(['company_id' => 1, 'passenger_account_id' => 1,
            'status' => 'blocked', 'updated_at' => '2026-10-20 09:00:00']);
        foreach ($types as $type) {
            $query = '?report=' . $type . '&from=2026-10-19&to=2026-10-20';
            $response = $this->getJson(self::BASE . '/reports' . $query)->assertOk();
            $this->assertEquals($type === 'users_without_bookings' ? 0 : 1, $response->json('data.summary.users'));
            $this->getJson(self::BASE . '/reports' . $query . '&terminal_id=11')->assertForbidden();
            $this->getJson(self::BASE . '/reports' . $query . '&route_id=4')->assertOk()->assertJsonCount(0, 'data.items');
        }
        $this->login(['mobile-app-view-users']);
        foreach ($types as $type) {
            $query = '?report=' . $type . '&from=2026-10-19&to=2026-10-20';
            $this->getJson(self::BASE . '/reports' . $query)->assertForbidden();
            $this->getJson(self::BASE . '/reports/export' . $query)->assertForbidden();
        }
        $this->getJson(self::BASE . '/reports?report=sales')->assertOk();
    }

    public function test_user_reports_validate_filters_and_handle_absent_controls_without_exposing_data(): void
    {
        $this->login();
        $this->getJson(self::BASE . '/reports?report=user_verification&verification=invalid')->assertUnprocessable();
        $this->getJson(self::BASE . '/reports?report=user_verification&status=paid')->assertUnprocessable();
        Schema::drop('mobile_app_passenger_controls');
        $this->getJson(self::BASE . '/reports?report=user_verification')->assertOk()->assertJsonPath('data.pagination.total', 4);
        $this->getJson(self::BASE . '/reports?report=user_restrictions')->assertStatus(503);
    }

    public function test_scoped_company_admin_can_blacklist_limit_reset_and_release_a_visible_passenger(): void
    {
        $user = $this->scopedCompanyAdmin();
        $options = $this->getJson(self::BASE . '/options')->assertOk();
        foreach (['users_update', 'users_blacklist', 'users_limits', 'users_password_reset', 'users_sessions_revoke'] as $flag) {
            $options->assertJsonPath('data.permissions.' . $flag, true);
        }
        DB::table('personal_access_tokens')->insert(['tokenable_type' => 'App\Models\PassengerAccount',
            'tokenable_id' => 1, 'name' => 'Mobile session', 'token' => 'fixture', 'created_at' => now(), 'updated_at' => now()]);
        $detail = $this->getJson(self::BASE . '/users/1')->assertOk()->assertJsonCount(1, 'data.sessions');
        $this->assertNotNull($detail->json('data.controls'));
        $body = ['status' => 'blacklisted', 'expected_status' => 'active', 'reason' => 'Abusive booking attempts'];
        $this->putJson(self::BASE . '/users/1/status', array_diff_key($body, ['reason' => true]))->assertUnprocessable();
        $this->putJson(self::BASE . '/users/1/status', $body)->assertOk()->assertJsonPath('data.sessions_revoked', 1);
        $this->assertDatabaseCount('mobile_app_blacklist_entries', 2);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertEquals(6, DB::table('tickets')->where('type', 'advance booking')->count());
        $this->putJson(self::BASE . '/users/1/limits', $this->limitsBody(1, ['daily_seat_limit' => 2]))->assertOk()
            ->assertJsonPath('data.limits.daily_seat_limit', 2);
        $revision = $this->getJson(self::BASE . '/users/1')->assertOk()->json('data.controls.revision');
        $this->postJson(self::BASE . '/users/1/require-password-reset', ['revision' => $revision, 'reason' => 'Recovery requested'])->assertOk();
        $this->putJson(self::BASE . '/users/1/status', ['status' => 'active', 'expected_status' => 'blacklisted', 'reason' => 'Restriction reviewed'])->assertOk();
        $this->assertDatabaseCount('mobile_app_blacklist_entries', 0);
        $this->postJson(self::BASE . '/users/1/revoke-sessions', ['reason' => 'Sessions reviewed'])->assertOk();
        $this->getJson(self::BASE . '/activity')->assertOk()->assertJsonCount(5, 'data.items');
        $this->getJson(self::BASE . '/activity?search=status')->assertOk()->assertJsonCount(2, 'data.items');
        $this->assertSame('[3]', $user->fresh()->route_ids);
        $this->getJson(self::BASE . '/bookings/102')->assertNotFound();
        $this->getJson(self::BASE . '/bookings/103')->assertNotFound();
    }

    public function test_admin_account_actions_cannot_target_hidden_route_unbooked_or_foreign_company_passengers(): void
    {
        $this->scopedCompanyAdmin();
        $body = $this->limitsBody(1, ['daily_seat_limit' => 0]);
        foreach ([3, 4, 5] as $id) {
            $this->getJson(self::BASE . '/users/' . $id)->assertNotFound();
            $this->putJson(self::BASE . '/users/' . $id . '/status', ['status' => 'blacklisted', 'expected_status' => 'active', 'reason' => 'Restricted target'])->assertNotFound();
            $this->putJson(self::BASE . '/users/' . $id . '/limits', $body)->assertNotFound();
            $this->postJson(self::BASE . '/users/' . $id . '/require-password-reset', ['revision' => $body['revision'], 'reason' => 'Restricted target'])->assertNotFound();
            $this->postJson(self::BASE . '/users/' . $id . '/revoke-sessions', ['reason' => 'Restricted target'])->assertNotFound();
        }
        $this->assertDatabaseCount('mobile_app_passenger_controls', 0);
        $this->assertDatabaseCount('mobile_app_blacklist_entries', 0);
        $this->assertDatabaseCount('mobile_app_admin_audits', 0);
    }

    public function test_admin_account_exception_still_requires_company_privileges_and_an_active_matching_role(): void
    {
        $user = $this->scopedCompanyAdmin();
        $modules = MobileAppPermissions::append([], true);
        foreach ($modules[0]['buttons'] as &$button) {
            if ($button['name'] === 'mobile-app-blacklist-users') { $button['allow'] = false; }
        }
        unset($button);
        DB::table('companies')->where('id', 1)->update(['modules' => json_encode($modules)]);
        $user->unsetRelations();
        $body = ['status' => 'blacklisted', 'expected_status' => 'active', 'reason' => 'Privilege check'];
        $this->getJson(self::BASE . '/options')->assertOk()->assertJsonPath('data.permissions.users_blacklist', false);
        $this->putJson(self::BASE . '/users/1/status', $body)->assertForbidden();
        $user->forceFill(['hide' => 1])->save();
        $this->postJson(self::BASE . '/users/1/revoke-sessions', ['reason' => 'Hidden staff'])->assertForbidden();
        $user->forceFill(['hide' => 0])->save();
        DB::table('roles')->where('id', $user->role_id)->update(['company_id' => 2]);
        $user->unsetRelations();
        $this->postJson(self::BASE . '/users/1/revoke-sessions', ['reason' => 'Mismatched role'])->assertForbidden();
        $this->assertDatabaseCount('mobile_app_admin_audits', 0);
    }


    private function monitoringSchema(): void
    {
        require_once database_path('migrations/2026_10_20_000006_create_mobile_app_monitoring.php');
        (new \CreateMobileAppMonitoring())->up();
        Schema::table('terminals', function (Blueprint $t) { $t->integer('advance_booking')->nullable(); });
        Schema::table('users', function (Blueprint $t) { $t->boolean('check_booking_minutes')->default(false); });
        Schema::table('schedules', function (Blueprint $t) { $t->string('name')->nullable(); $t->boolean('hide')->default(false); });
        Schema::table('schedule_details', function (Blueprint $t) { foreach (['departure_date','departure_id','destination_id','bus_class_id'] as $c) { $t->text($c)->nullable(); } });
        $this->table('cities', ['name','company_id','hide']);
        $this->table('bus_classes', ['name','company_id']);
        $this->table('route_online_terminals', ['company_id','route_id','terminal_id']);
        $this->table('schedule_terminal_visibilities', ['company_id','schedule_id','terminal_id','visibility']);
        $this->table('terminal_visibilities', ['company_id','route_id','departure_city_id','destination_city_id','online_visibilty','booking_minutes']);
        $this->table('terminal_time_differences', ['company_id','route_id','terminal_id','time_difference']);
        $this->table('drop_schedules', ['company_id','schedule_id','schedule_date']);
        DB::table('terminals')->where('id',10)->update(['is_online_terminal'=>1,'advance_booking'=>10]);
        DB::table('terminals')->where('id',11)->update(['is_online_terminal'=>1]);
        DB::table('cities')->insert([['id'=>1,'company_id'=>1,'name'=>'Origin','hide'=>0],['id'=>2,'company_id'=>1,'name'=>'Destination','hide'=>0]]);
        DB::table('bus_classes')->insert(['id'=>1,'company_id'=>1,'name'=>'Executive']);
        DB::table('schedules')->where('id',77)->update(['name'=>'Afternoon']);
        DB::table('schedule_details')->where('id',77)->update(['departure_date'=>'2026-10-21','departure_id'=>1,'destination_id'=>2,'bus_class_id'=>1]);
        DB::table('schedule_terminal_visibilities')->insert(['company_id'=>1,'schedule_id'=>77,'terminal_id'=>10,'visibility'=>1]);
        DB::table('terminal_visibilities')->insert(['company_id'=>1,'route_id'=>3,'departure_city_id'=>1,'destination_city_id'=>2,'online_visibilty'=>0,'booking_minutes'=>null]);
        DB::table('mobile_app_runtime')->insert(['company_id'=>1,'terminal_id'=>10,'booking_user_id'=>1,'tracking_started_at'=>'2026-10-19 00:00:00','last_seen_at'=>'2026-10-20 04:00:00']);
    }

    public function test_availability_explains_routes_and_schedules_without_changing_grants(): void
    {
        $this->monitoringSchema(); $user=$this->login();
        DB::table('route_online_terminals')->insert(['company_id'=>1,'route_id'=>4,'terminal_id'=>11]);
        $routes=$this->getJson(self::BASE.'/availability/routes?date=2026-10-21')->assertOk();
        $routes->assertJsonPath('data.context.confirmed',true)->assertJsonPath('data.summary.allowed',1)->assertJsonPath('data.summary.not_allowed',1);
        $this->assertCount(2,$routes->json('data.items'));
        $this->assertSame(1,$routes->json('data.items.0.schedules'));
        $extra=(array) DB::table('schedule_details')->where('id',77)->first(); $extra['id']=78;
        DB::table('schedule_details')->insert($extra);
        $this->getJson(self::BASE.'/availability/routes?date=2026-10-21')->assertOk()->assertJsonPath('data.items.0.schedules',1);
        $this->getJson(self::BASE.'/availability/schedules?date=2026-10-21&route_id=3')->assertOk()
            ->assertJsonPath('data.items.0.status','allowed')->assertJsonPath('data.items.0.origin','Origin')->assertJsonPath('data.items.0.departure_time','14:00:00');
        $this->getJson(self::BASE.'/availability/schedules?date=2026-10-21&route_id=4')->assertOk()->assertJsonCount(0,'data.items');
        $this->assertSame('all',$user->fresh()->route_ids);
    }

    public function test_schedule_monitoring_reports_each_visibility_block_and_soft_deleted_rule(): void
    {
        $this->monitoringSchema();$this->login();
        $url=self::BASE.'/availability/schedules?date=2026-10-21&route_id=3';
        foreach ([['routes','hide',1,0],['schedules','hide',1,0],['cities','hide',1,0],['schedule_terminal_visibilities','visibility',0,1],['terminal_visibilities','online_visibilty',1,0]] as [$table,$column,$blocked,$restored]) {
            DB::table($table)->update([$column=>$blocked]);
            $this->getJson($url)->assertOk()->assertJsonPath('data.items.0.status','not_allowed');
            DB::table($table)->update([$column=>$restored]);
            $this->getJson($url)->assertOk()->assertJsonPath('data.items.0.status','allowed');
        }
        DB::table('terminal_visibilities')->update(['online_visibilty'=>1,'deleted_at'=>now()]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.items.0.status','allowed');
        DB::table('schedule_terminal_visibilities')->update(['deleted_at'=>now()]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.items.0.status','not_allowed');
    }

    public function test_schedule_monitoring_uses_run_date_for_cancellations_and_exclusive_advance_window(): void
    {
        $this->monitoringSchema();$this->login();
        $url=self::BASE.'/availability/schedules?date=2026-10-21&route_id=3';
        DB::table('schedule_details')->update(['schedule_date'=>'2026-10-20']);
        DB::table('drop_schedules')->insert(['company_id'=>1,'schedule_id'=>77,'schedule_date'=>'2026-10-21']);
        $this->getJson($url)->assertOk()->assertJsonPath('data.items.0.status','allowed');
        DB::table('drop_schedules')->update(['schedule_date'=>'2026-10-20']);
        $this->getJson($url)->assertOk()->assertJsonPath('data.items.0.reason','Departure is cancelled.');
        DB::table('drop_schedules')->update(['deleted_at'=>now()]);
        DB::table('terminals')->where('id',10)->update(['advance_booking'=>1]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.items.0.status','not_open');
        DB::table('terminals')->where('id',10)->update(['advance_booking'=>2]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.items.0.status','allowed');
    }

    public function test_schedule_monitoring_obeys_booking_opening_minutes_and_terminal_offset(): void
    {
        $this->monitoringSchema();$this->login();
        DB::table('users')->where('id',1)->update(['check_booking_minutes'=>1]);
        DB::table('terminal_visibilities')->update(['booking_minutes'=>60]);
        DB::table('terminal_time_differences')->insert(['company_id'=>1,'route_id'=>3,'terminal_id'=>10,'time_difference'=>60]);
        $url=self::BASE.'/availability/schedules?date=2026-10-21&route_id=3';
        Carbon::setTestNow(Carbon::parse('2026-10-21 13:59:59','Asia/Karachi'));
        $this->getJson($url)->assertOk()->assertJsonPath('data.items.0.status','not_open');
        Carbon::setTestNow(Carbon::parse('2026-10-21 14:00:00','Asia/Karachi'));
        $this->getJson($url)->assertOk()->assertJsonPath('data.items.0.status','allowed');
    }

    public function test_availability_keeps_staff_company_route_and_terminal_boundaries(): void
    {
        $this->monitoringSchema();$this->login([],false,'[3]');
        $this->getJson(self::BASE.'/availability/routes?date=2026-10-21')->assertOk()->assertJsonCount(1,'data.items')->assertJsonCount(1,'data.context.terminals');
        foreach ([4,8] as $id) {$this->getJson(self::BASE.'/availability/schedules?date=2026-10-21&route_id='.$id)->assertNotFound();}
        $this->getJson(self::BASE.'/availability/routes?date=2026-10-21&mobile_terminal_id=11')->assertForbidden();
        $this->login(['mobile-app-view-bookings']);
        $this->getJson(self::BASE.'/availability/routes?date=2026-10-21')->assertForbidden();
        $this->getJson(self::BASE.'/availability/schedules?date=2026-10-21&route_id=3')->assertForbidden();
    }

    public function test_availability_preview_never_claims_unconfirmed_booking_opening_rules(): void
    {
        $this->monitoringSchema();$this->login();DB::table('mobile_app_runtime')->delete();
        $this->getJson(self::BASE.'/availability/routes?date=2026-10-21')->assertOk()->assertJsonPath('data.context.confirmed',false)->assertJsonPath('data.items.0.status','needs_setup');
        DB::table('terminal_visibilities')->update(['booking_minutes'=>60]);
        $this->getJson(self::BASE.'/availability/schedules?date=2026-10-21&route_id=3&mobile_terminal_id=10')->assertOk()->assertJsonPath('data.items.0.status','needs_setup');
        $this->getJson(self::BASE.'/availability/routes?date=2026-10-19')->assertUnprocessable();
        $this->getJson(self::BASE.'/availability/routes?date=2027-10-21')->assertUnprocessable();
        $this->getJson(self::BASE.'/availability/routes?date=2026-10-21&status=anything')->assertUnprocessable();
    }

    public function test_login_graph_uses_pakistan_days_and_keeps_scope_without_exposing_accounts(): void
    {
        $this->monitoringSchema();$this->login([],false,'[3]');
        foreach ([[1,1,'2026-10-19 18:59:59',1],[1,1,'2026-10-19 19:00:00',0],[1,2,'2026-10-20 01:00:00',1],[1,3,'2026-10-20 01:00:00',1],[2,4,'2026-10-20 01:00:00',1]] as [$company,$account,$time,$first]) {
            DB::table('mobile_app_login_events')->insert(['company_id'=>$company,'passenger_account_id'=>$account,'kind'=>'login','first_login'=>$first,'occurred_at'=>$time]);
        }
        $response=$this->getJson(self::BASE.'/engagement?days=7')->assertOk()->assertJsonPath('data.total_logins',2)->assertJsonPath('data.period_logins',2)
            ->assertJsonPath('data.unique_users',1)->assertJsonPath('data.first_logins',1)->assertJsonPath('data.downloads.available',false);
        $daily=collect($response->json('data.daily'))->keyBy('date');
        $this->assertSame(1,$daily['2026-10-19']['logins']);$this->assertSame(1,$daily['2026-10-20']['logins']);
        $this->assertNull($daily['2026-10-18']['logins']);
        $this->assertStringNotContainsString('passenger_account_id',$response->getContent());
        DB::table('personal_access_tokens')->delete();
        $this->getJson(self::BASE.'/engagement?days=7')->assertOk()->assertJsonPath('data.total_logins',2);
        $this->getJson(self::BASE.'/engagement?days=7&terminal_id=11')->assertForbidden();
        $this->login();
        $this->getJson(self::BASE.'/engagement?days=30')->assertOk()->assertJsonPath('data.total_logins',4)->assertJsonCount(30,'data.daily');
        $this->getJson(self::BASE.'/engagement?days=365')->assertUnprocessable();
        $this->login(['mobile-app-view-dashboard']);$this->getJson(self::BASE.'/engagement')->assertForbidden();
    }

    public function test_missing_login_tracking_is_unknown_rather_than_zero(): void
    {
        $this->login();
        $this->getJson(self::BASE.'/engagement')->assertOk()->assertJsonPath('data.available',false)->assertJsonPath('data.total_logins',null);
        $this->monitoringSchema();DB::table('mobile_app_runtime')->delete();
        $this->getJson(self::BASE.'/engagement')->assertOk()->assertJsonPath('data.available',false)->assertJsonPath('data.total_logins',null);
        $this->getJson(self::BASE.'/availability/routes?date=2026-10-21&mobile_terminal_id=20')->assertForbidden();
    }

}
