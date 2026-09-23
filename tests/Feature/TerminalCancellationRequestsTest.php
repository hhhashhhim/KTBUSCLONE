<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\OnlineTerminalCancellationController;
use App\Http\Controllers\Booking\BookingController;
use App\Models\BookkaruApiLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Entirely in-memory: no application or production data is modified. */
class TerminalCancellationRequestsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'cancellation_test', 'database.connections.cancellation_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'services.terminal_cancellations.approval_enabled' => true]);
        DB::setDefaultConnection('cancellation_test');
        Schema::clearResolvedInstance('db.schema');
        $this->table('roles', ['permissions']);
        $this->table('users', ['name', 'company_id', 'role_id', 'terminal_id']);
        $this->table('terminals', ['name', 'company_id', 'cancellation_allowed', 'is_online_terminal']);
        $this->table('tickets', ['company_id', 'invoice_id', 'seat_no', 'seat_fare', 'discount', 'customer_id', 'schedule_id', 'type', 'transaction_id', 'date', 'schedule_date', 'schedule_time', 'refund_reason', 'refund_percentage', 'refund_amount']);
        $this->table('customers', ['name', 'cnic', 'contact']);
        $this->table('schedules', ['route_id']);
        $this->table('routes', ['name']);
        $this->table('ticket_e_l_t_s', ['ticket_id']);
        $this->table('booking_cancels', ['company_id', 'ticket_id', 'percentage', 'reason', 'type', 'added_by']);
        $this->table('activity_logs', ['activity_by', 'message', 'requested_host', 'company_id']);
        $this->table('bookkaru_api_logs', ['company_id', 'request_id', 'invoice_id', 'normalized_invoice_id', 'booking_reference', 'seat_numbers', 'request_payload', 'response_payload', 'status', 'approval_status', 'cancellation_status', 'success', 'duplicate', 'unauthorized', 'ip_address', 'user_agent', 'approved_by', 'rejected_by', 'approved_at', 'rejected_at', 'cancelled_at', 'rejection_reason', 'exception_message']);
        DB::table('roles')->insert(['id' => 1, 'permissions' => json_encode([['allow' => true, 'childs' => [
            ['name' => 'online-terminal-cancellation', 'allow' => true, 'buttons' => [
                ['name' => 'approve-request', 'allow' => true], ['name' => 'reject-request', 'allow' => true],
                ['name' => 'cancel-ticket', 'allow' => true], ['name' => 'reserved-cancel', 'allow' => true],
            ]],
        ]]])]);
        User::create(['id' => 1, 'name' => 'Requesting Clerk', 'company_id' => 1, 'role_id' => 1, 'terminal_id' => 1]);
        Auth::setUser(User::find(1));
        DB::table('terminals')->insert(['id' => 1, 'name' => 'Test Terminal', 'company_id' => 1, 'cancellation_allowed' => 1, 'is_online_terminal' => 0]);
        for ($seat = 1; $seat <= 4; $seat++) {
            Ticket::create(['id' => $seat, 'company_id' => 1, 'invoice_id' => 100, 'seat_no' => (string) $seat,
                'seat_fare' => 9750, 'discount' => 2750, 'type' => 'booked', 'transaction_id' => 'TEST',
                'date' => '2026-09-23', 'schedule_date' => '2026-09-23', 'schedule_time' => '16:00:00']);
        }
    }

    private function table(string $name, array $columns): void
    {
        Schema::create($name, function (Blueprint $table) use ($columns) {
            $table->increments('id');
            foreach ($columns as $column) $table->text($column)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function listing(array $filters = []): array
    {
        $response = app(OnlineTerminalCancellationController::class)->requests(new Request($filters));
        $this->assertSame(200, $response->getStatusCode());
        return $response->getData(true)['data'];
    }

    private function submit(int $ticketId = 1)
    {
        return app(BookingController::class)->cancelingBooking(new Request([
            'ticket_id' => $ticketId, 'percentage' => 0, 'remarks' => 'Test cancellation',
        ]));
    }

    public function test_single_seat_request_uses_only_that_seat_and_requesting_user(): void
    {
        $response = $this->submit();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['pending']);
        $this->assertSame(4, Ticket::count());
        $this->assertSame(1, BookkaruApiLog::count());
        // Even a stale invoice-level preview must not leak into a seat row.
        BookkaruApiLog::first()->update(['response_payload' => ['data' => ['refund_amount' => 28000]]]);
        $row = $this->listing(['tab' => 'pending'])['requests'][0];
        $this->assertSame(['1'], $row['seat_numbers']);
        $this->assertEquals(7000, $row['fare']);
        $this->assertEquals(7000, $row['refund_amount']);
        $this->assertSame('Requesting Clerk', $row['requested_by']);
        $this->assertSame([1], BookkaruApiLog::first()->request_payload['ticket_ids']);
        $this->assertSame(1, BookkaruApiLog::first()->company_id);
        $this->assertSame(422, $this->submit()->getStatusCode());
        $this->assertSame(1, BookkaruApiLog::count());
    }

    public function test_bulk_portal_requests_are_independent_and_tabs_count_decisions(): void
    {
        $response = app(BookingController::class)->cancelingAllBooking(new Request([
            'cancelAllSeat' => [1, 2, 3, 4], 'percentage' => 0, 'reason' => 'Test bulk',
        ]));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(4, $response->getData(true)['data']['request_ids']);
        $data = $this->listing(['tab' => 'pending']);
        $this->assertSame(['pending' => 4, 'completed' => 0], $data['counts']);
        foreach ($data['requests'] as $row) {
            $this->assertCount(1, $row['seat_numbers']);
            $this->assertEquals(7000, $row['refund_amount']);
        }
        $id = BookkaruApiLog::first()->id;
        $approved = app(OnlineTerminalCancellationController::class)->approveRequest(new Request(['id' => $id, 'deduction_percentage' => 10]));
        $this->assertSame(200, $approved->getStatusCode(), $approved->getContent());
        $this->assertEquals(6300, $approved->getData(true)['data']['refund_amount']);
        $this->assertSame(3, Ticket::count());
        $this->assertEquals(6300, Ticket::withTrashed()->find(1)->refund_amount);
        $this->assertSame('booked', Ticket::find(2)->type);
        $completed = $this->listing(['tab' => 'completed']);
        $this->assertSame(['pending' => 3, 'completed' => 1], $completed['counts']);
        $this->assertCount(1, $completed['requests']);
        $this->assertEquals(6300, $completed['requests'][0]['refund_amount']);
        $this->assertEquals(700, $completed['requests'][0]['deduction_amount']);
        $reject = app(OnlineTerminalCancellationController::class)->rejectRequest(new Request(['id' => $id + 1, 'rejection_reason' => 'Test rejection']));
        $this->assertSame(200, $reject->getStatusCode());
        $this->assertSame(['pending' => 2, 'completed' => 2], $this->listing(['tab' => 'completed'])['counts']);
        $this->assertSame(3, Ticket::count());
    }

    public function test_legacy_grouped_request_is_displayed_per_seat_not_total_refund(): void
    {
        $this->submit();
        $log = BookkaruApiLog::first();
        $log->update(['seat_numbers' => ['1', '2', '3', '4'], 'request_payload' => ['deduction_percentage' => 0],
            'response_payload' => ['data' => ['refund_amount' => 28000]]]);
        $this->migrateCancellationOwnership();
        $rows = $this->listing(['tab' => 'pending'])['requests'];
        $this->assertCount(4, $rows);
        $this->assertCount(4, array_unique(array_column($rows, 'row_key')));
        foreach ($rows as $row) {
            $this->assertEquals(7000, $row['refund_amount']);
            $this->assertEquals(28000, $row['request_fare']);
        }
        $log->update(['status' => 'cancelled', 'response_payload' => ['data' => ['refund_amount' => 25200, 'refunds' => array_map(fn ($id) => [
            'ticket_id' => $id, 'seat_no' => (string) $id, 'refund_amount' => 6300, 'deduction_amount' => 700,
        ], [1, 2, 3, 4])]]]);
        foreach ($this->listing(['tab' => 'completed'])['requests'] as $row) $this->assertEquals(6300, $row['refund_amount']);
    }

    public function test_disallowed_terminal_creates_no_request_and_authorized_booking_user_can_submit(): void
    {
        DB::table('terminals')->where('id', 1)->update(['cancellation_allowed' => 0]);
        $this->assertSame(422, $this->submit()->getStatusCode());
        $this->assertSame(0, BookkaruApiLog::count());
        DB::table('terminals')->where('id', 1)->update(['cancellation_allowed' => 1]);
        DB::table('roles')->where('id', 1)->update(['permissions' => json_encode([['allow' => true, 'childs' => [
            ['name' => 'booking', 'allow' => true, 'buttons' => [['name' => 'cancel-ticket', 'allow' => true]]],
        ]]])]);
        $this->assertSame(200, $this->submit()->getStatusCode());
        $this->assertSame(403, app(OnlineTerminalCancellationController::class)->pendingCount()->getStatusCode());
        $this->assertSame(1, BookkaruApiLog::count());
    }

    public function test_counts_cover_all_pages_and_do_not_include_other_companies(): void
    {
        $this->submit();
        $log = BookkaruApiLog::first();
        for ($i = 0; $i < 102; $i++) {
            $copy = $log->replicate(); $copy->request_id = 'TEST-' . $i; $copy->save();
        }
        Ticket::create(['company_id' => 2, 'invoice_id' => 200, 'seat_no' => '1']);
        $other = $log->replicate(); $other->request_id = 'OTHER'; $other->company_id = 2; $other->invoice_id = 200; $other->normalized_invoice_id = 200; $other->save();
        $first = $this->listing(['tab' => 'pending']);
        $this->assertSame(103, $first['counts']['pending']);
        $this->assertSame(2, $first['pagination']['last_page']);
        $this->assertCount(100, $first['requests']);
        $this->assertCount(3, $this->listing(['tab' => 'pending', 'page' => 2])['requests']);
    }

    public function test_saved_ticket_id_ignores_old_seat_versions_and_negative_discount_is_preserved(): void
    {
        Ticket::find(1)->update(['seat_fare' => 4100, 'discount' => -550]);
        $old = Ticket::find(1)->replicate();
        $old->seat_fare = 20000; $old->discount = 0; $old->deleted_at = now(); $old->save();
        $this->assertSame(200, $this->submit()->getStatusCode());
        $this->assertEquals(4650, $this->listing(['tab' => 'pending'])['requests'][0]['refund_amount']);
        $response = app(OnlineTerminalCancellationController::class)->approveRequest(new Request(['id' => BookkaruApiLog::first()->id, 'deduction_percentage' => 0]));
        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals(4650, $response->getData(true)['data']['refund_amount']);
        $this->assertSame(3, Ticket::count());
    }

    public function test_unpaid_reservation_still_cancels_directly(): void
    {
        Ticket::find(1)->update(['type' => 'advance booking']);
        $response = $this->submit();
        $this->assertIsArray($response);
        $this->assertSame(0, BookkaruApiLog::count());
        $this->assertSame(3, Ticket::count());
    }

    public function test_pending_has_no_age_limit_and_completed_defaults_to_ten_decision_days(): void
    {
        \Carbon\Carbon::setTestNow('2026-09-23 12:00:00');
        try {
            $this->submit();
            $pending = BookkaruApiLog::first();
            $pending->update(['created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-01 10:00:00']);
            foreach ([
                ['status' => 'cancelled', 'cancelled_at' => '2026-09-14 00:00:00'],
                ['status' => 'rejected', 'rejected_at' => '2026-09-23 11:00:00'],
                ['status' => 'cancelled', 'cancelled_at' => '2026-09-13 23:59:59'],
                ['status' => 'failed', 'updated_at' => '2026-01-01 10:00:00'],
            ] as $i => $decision) {
                $log = $pending->replicate();
                $log->request_id = 'DECISION-' . $i;
                $log->fill($decision); $log->save();
            }
            $inbox = $this->listing();
            $this->assertCount(1, $inbox['requests']);
            $this->assertSame('pending', $inbox['requests'][0]['status']);
            $this->assertSame(['pending' => 1, 'completed' => 2], $inbox['counts']);
            $completed = $this->listing(['tab' => 'completed']);
            $this->assertCount(2, $completed['requests']);
            $this->assertTrue($completed['scope']['recent_completed_only']);
            $this->assertSame('2026-09-14', $completed['scope']['from']);
            $history = $this->listing(['tab' => 'completed', 'invoiceFilter' => '100']);
            $this->assertFalse($history['scope']['recent_completed_only']);
            $this->assertCount(4, $history['requests']);
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_older_completed_filter_results_remain_server_paginated(): void
    {
        $this->submit();
        $log = BookkaruApiLog::first();
        $log->update(['status' => 'cancelled', 'cancelled_at' => '2020-01-01 00:00:00']);
        for ($i = 0; $i < 60; $i++) {
            $copy = $log->replicate(); $copy->request_id = 'OLD-' . $i; $copy->save();
        }
        $this->assertCount(0, $this->listing(['tab' => 'completed'])['requests']);
        $page = $this->listing(['tab' => 'completed', 'invoiceFilter' => '100', 'page' => 2, 'per_page' => 25]);
        $this->assertCount(25, $page['requests']);
        $this->assertSame(61, $page['counts']['completed']);
        $this->assertSame(61, $page['pagination']['total']);
        $this->assertSame(3, $page['pagination']['last_page']);
        $this->assertSame(2, $page['pagination']['current_page']);
        $this->assertSame(25, $page['pagination']['per_page']);
        $last = $this->listing(['tab' => 'completed', 'invoiceFilter' => '100', 'page' => 3, 'per_page' => 25]);
        $this->assertCount(11, $last['requests']);
    }

    private function migrateCancellationOwnership(): void
    {
        require_once database_path('migrations/2026_09_23_000002_add_company_to_terminal_cancellation_requests.php');
        (new \AddCompanyToTerminalCancellationRequests())->up();
    }

    public function test_migration_backfills_company_and_display_references_without_changing_approval_or_dates(): void
    {
        $this->submit();
        $log = BookkaruApiLog::first();
        $log->update(['company_id' => null, 'request_payload' => ['cancellation_reason' => 'Keep this'], 'updated_at' => '2020-01-01 10:00:00']);
        $oldTicket = Ticket::find(1)->replicate();
        $oldTicket->deleted_at = now(); $oldTicket->seat_fare = 99999; $oldTicket->save();
        $other = $log->replicate(); $other->company_id = 2; $other->request_id = 'OTHER-COMPANY'; $other->save();
        $this->migrateCancellationOwnership();
        $log->refresh();
        $this->assertSame(1, $log->company_id);
        $this->assertSame([1], $log->request_payload['display_ticket_ids']);
        $this->assertArrayNotHasKey('ticket_ids', $log->request_payload, 'Do not change the approval target for legacy requests');
        $this->assertSame('Keep this', $log->request_payload['cancellation_reason']);
        $this->assertSame('2020-01-01 10:00:00', $log->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame(2, $other->fresh()->company_id);
        $this->assertSame([], $other->fresh()->request_payload['display_ticket_ids']);
        $this->migrateCancellationOwnership(); // Safe to resume after an interrupted deployment.
        $this->assertSame([1], $log->fresh()->request_payload['display_ticket_ids']);
        $this->assertEquals(7000, $this->listing()['requests'][0]['refund_amount']);
    }

    public function test_migration_adds_company_column_to_old_schema(): void
    {
        $this->submit();
        Schema::table('bookkaru_api_logs', fn (Blueprint $table) => $table->dropColumn('company_id'));
        $this->migrateCancellationOwnership();
        $this->assertTrue(Schema::hasColumn('bookkaru_api_logs', 'company_id'));
        $this->assertSame(1, BookkaruApiLog::first()->company_id);
        $this->assertSame([1], BookkaruApiLog::first()->request_payload['ticket_ids']);
    }

    public function test_listing_counts_never_query_tickets_and_page_details_are_batched(): void
    {
        $this->submit();
        $log = BookkaruApiLog::first();
        for ($i = 0; $i < 30; $i++) {
            $copy = $log->replicate(); $copy->request_id = 'BATCH-' . $i; $copy->save();
        }
        DB::enableQueryLog(); DB::flushQueryLog();
        try {
            $data = $this->listing(['per_page' => 25]);
            $queries = collect(DB::getQueryLog())->pluck('query');
            $this->assertSame(31, $data['counts']['pending']);
            $this->assertCount(25, $data['requests']);
            $counts = $queries->filter(fn ($sql) => strpos($sql, 'count(*)') !== false && strpos($sql, 'bookkaru_api_logs') !== false);
            $this->assertCount(2, $counts, 'Reuse the tab count rather than counting again for pagination');
            foreach ($counts as $sql) $this->assertStringNotContainsString('tickets', $sql);
            $this->assertCount(1, $queries->filter(fn ($sql) => strpos($sql, 'from "tickets"') !== false));
            DB::flushQueryLog();
            $response = app(OnlineTerminalCancellationController::class)->pendingCount();
            $this->assertSame(31, $response->getData(true)['pending_count']);
            foreach (DB::getQueryLog() as $query) $this->assertStringNotContainsString('tickets', $query['query']);
        } finally {
            DB::disableQueryLog(); DB::flushQueryLog();
        }
    }

    public function test_foreign_company_request_cannot_be_approved_even_with_matching_invoice(): void
    {
        $this->submit();
        $log = BookkaruApiLog::first(); $log->update(['company_id' => 2]);
        $this->assertCount(0, $this->listing()['requests']);
        $this->assertSame(0, app(OnlineTerminalCancellationController::class)->pendingCount()->getData(true)['pending_count']);
        $response = app(OnlineTerminalCancellationController::class)->approveRequest(new Request(['id' => $log->id]));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(4, Ticket::count());
    }

    public function test_external_requests_derive_company_from_tickets_not_input(): void
    {
        config(['services.online_terminals.api_key' => 'local-test-key']);
        $payload = ['request_id' => 'EXTERNAL', 'invoice_id' => 100, 'company_id' => 99,
            'transaction_id' => 'TEST', 'seat_numbers' => ['1'], 'cancellation_reason' => 'Test only', 'source' => 'Test Terminal'];
        $request = new Request($payload);
        $request->headers->set('X-ONLINE-TERMINAL-API-KEY', 'local-test-key');
        $response = app(OnlineTerminalCancellationController::class)->cancelSeat($request);
        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $log = BookkaruApiLog::where('request_id', 'EXTERNAL')->first();
        $this->assertSame(1, $log->company_id);
        $this->assertSame([1], $log->request_payload['ticket_ids']);

        $payload['request_id'] = 'EXTERNAL-FAILURE'; $payload['seat_numbers'] = ['99'];
        $request = new Request($payload);
        $request->headers->set('X-ONLINE-TERMINAL-API-KEY', 'local-test-key');
        $this->assertSame(422, app(OnlineTerminalCancellationController::class)->cancelSeat($request)->getStatusCode());
        $failed = BookkaruApiLog::where('request_id', 'EXTERNAL-FAILURE')->first();
        $this->assertSame(1, $failed->company_id);
        $this->assertSame([], $failed->request_payload['display_ticket_ids']);
    }
}
