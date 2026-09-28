<?php

namespace Tests\Feature;

use App\Http\Controllers\Report\TerminalTicketActivityController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TerminalTicketActivityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'activity_test', 'database.connections.activity_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::setDefaultConnection('activity_test');
        Schema::clearResolvedInstance('db.schema');
        foreach ([
            'users' => ['company_id', 'name', 'role_id', 'is_super_admin', 'route_ids', 'terminal_id'],
            'roles' => ['permissions'], 'customers' => ['name', 'cnic', 'contact'], 'terminals' => ['name'],
            'routes' => ['name'], 'bus_classes' => ['name'], 'buses' => ['bus_number'],
            'tickets' => ['company_id', 'invoice_id', 'terminal_id', 'route_id', 'customer_id', 'bus_id', 'bus_class_id', 'type', 'seat_no', 'schedule_date', 'schedule_time', 'transaction_id', 'seat_fare', 'discount', 'refund_amount', 'refund_reason', 'remarks', 'added_by', 'updated_by'],
            'booking_cancels' => ['company_id', 'ticket_id', 'type', 'reason', 'added_by'],
            'ticket_reschedules' => ['company_id', 'old_ticket_id', 'new_ticket_id', 'reason', 'added_by'],
            'ticket_advanced_bookeds' => ['company_id', 'ticket_id', 'added_by'],
            'bookkaru_api_logs' => ['company_id', 'normalized_invoice_id', 'invoice_id', 'request_payload', 'seat_numbers', 'status', 'approved_at', 'approved_by', 'rejected_at', 'rejected_by', 'cancelled_at'],
        ] as $name => $columns) Schema::create($name, function (Blueprint $table) use ($columns) {
            $table->increments('id'); foreach ($columns as $column) $table->text($column)->nullable(); $table->timestamps(); $table->softDeletes();
        });
        DB::table('roles')->insert(['id' => 1, 'permissions' => json_encode([['allow' => true, 'childs' => [['name' => 'terminal-sale', 'allow' => true, 'buttons' => []]]]])]);
        DB::table('users')->insert([
            ['id' => 1, 'company_id' => 1, 'name' => 'Report user', 'role_id' => 1, 'is_super_admin' => 1, 'route_ids' => '[1]', 'terminal_id' => 1],
            ['id' => 2, 'company_id' => 1, 'name' => 'Cancellation user', 'role_id' => 1, 'is_super_admin' => 0, 'route_ids' => '[1]', 'terminal_id' => 1],
        ]);
        Auth::setUser(User::find(1));
        foreach (['terminals', 'routes', 'bus_classes'] as $table) DB::table($table)->insert(['id' => 1, 'name' => 'Test ' . $table]);
        DB::table('customers')->insert(['id' => 1, 'name' => 'Test <script>alert(1)</script>', 'cnic' => '123', 'contact' => '456', 'deleted_at' => '2026-09-26 00:00:00']);
        foreach ([1 => 'reschedule', 2 => 'canceled', 3 => 'canceled', 4 => 'booked'] as $id => $type) {
            DB::table('tickets')->insert(['id' => $id, 'company_id' => 1, 'invoice_id' => 1234, 'terminal_id' => 1, 'route_id' => 1, 'customer_id' => 1, 'bus_class_id' => 1,
                'seat_no' => (string) $id, 'type' => $type, 'seat_fare' => 7000, 'discount' => 500, 'refund_amount' => $id === 2 ? 6500 : null,
                'schedule_date' => '2026-09-25', 'schedule_time' => '16:00:00', 'added_by' => 1, 'updated_by' => 1,
                'created_at' => '2026-09-23 10:00:00', 'updated_at' => '2026-09-24 12:00:00', 'deleted_at' => $id === 4 ? null : '2026-09-24 12:00:00']);
        }
        DB::table('ticket_reschedules')->insert(['company_id' => 1, 'old_ticket_id' => 1, 'new_ticket_id' => 2, 'reason' => 'Time changed', 'added_by' => 2, 'created_at' => '2026-09-24 10:00:00']);
        DB::table('booking_cancels')->insert([
            ['company_id' => 1, 'ticket_id' => 2, 'type' => 'booked', 'reason' => 'Customer request', 'added_by' => 2, 'created_at' => '2026-09-24 12:00:00'],
            ['company_id' => 1, 'ticket_id' => 3, 'type' => 'advance booking', 'reason' => 'auto cancel', 'added_by' => 0, 'created_at' => '2026-09-24 11:00:00'],
        ]);
        DB::table('ticket_advanced_bookeds')->insert(['company_id' => 1, 'ticket_id' => 3, 'added_by' => 1, 'created_at' => '2026-09-23 10:00:00']);
        DB::table('bookkaru_api_logs')->insert(['company_id' => 1, 'normalized_invoice_id' => 1234, 'request_payload' => json_encode(['ticket_ids' => [2], 'requested_by' => 1, 'cancellation_reason' => 'Refund please']),
            'seat_numbers' => '["2"]', 'status' => 'cancelled', 'created_at' => '2026-09-24 11:00:00', 'approved_at' => '2026-09-24 11:30:00', 'approved_by' => 2, 'cancelled_at' => '2026-09-24 12:00:00']);
    }

    private function details($invoice = '1234'): array
    {
        return app(TerminalTicketActivityController::class)->details(new Request(['invoice_id' => $invoice]));
    }

    public function test_invoice_details_include_each_seat_history_and_print_safely(): void
    {
        $report = $this->details();
        $this->assertCount(4, $report['tickets']);
        $this->assertTrue($report['tickets'][0]['historical']);
        $this->assertSame('Cancelled', $report['tickets'][1]['status']);
        $this->assertSame('Expired reservation', $report['tickets'][2]['status']);
        $this->assertSame('booked', $report['tickets'][3]['status']);
        $this->assertSame(6500.0, $report['tickets'][1]['recorded_refund']);
        $this->assertSame(6500.0, $report['tickets'][3]['net_fare']);
        $this->assertStringContainsString('<script>', $report['tickets'][0]['passenger']);
        $this->assertSame('Cancellation user', $report['events']->firstWhere('action', 'Cancelled')['actor']);
        $this->assertSame('System (automatic cancellation)', $report['events']->firstWhere('action', 'Reservation expired')['actor']);
        $this->assertSame('Report user', $report['events']->firstWhere('action', 'Cancellation requested')['actor']);
        $this->assertSame('Cancellation user', $report['events']->firstWhere('action', 'Cancellation request approved')['actor']);
        $this->assertSame('Not recorded', $report['events']->firstWhere('action', 'Record last updated')['actor']);
        $this->assertSame('2', $report['events']->firstWhere('action', 'Cancellation requested')['seat']);
        $dates = $report['events']->pluck('at')->all(); $sorted = $dates; sort($sorted); $this->assertSame($sorted, $dates);
        $html = app(TerminalTicketActivityController::class)->print(new Request(['invoice_id' => '1234']))->render();
        $this->assertStringContainsString('Expired reservation', $html);
        $this->assertStringContainsString('Cancellation user', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertSame(4, DB::table('tickets')->count());
    }

    public function test_invoice_lookup_is_exact_and_company_scoped(): void
    {
        DB::table('tickets')->where('id', 4)->update(['company_id' => 2]);
        $this->assertCount(3, $this->details()['tickets']);
        $this->expectException(HttpException::class);
        $this->details('123');
    }

    public function test_scope_is_enforced_for_json_and_print_and_unrelated_seat_requests_are_hidden(): void
    {
        Auth::user()->is_super_admin = 0;
        DB::table('tickets')->where('id', 1)->update(['terminal_id' => 2]);
        DB::table('tickets')->where('id', 2)->update(['updated_by' => 2]);
        DB::table('tickets')->where('id', 3)->update(['route_id' => 2]);
        $report = $this->details();
        $this->assertSame([4], $report['tickets']->pluck('id')->all());
        $this->assertNull($report['events']->firstWhere('action', 'Cancellation requested'));
        $html = app(TerminalTicketActivityController::class)->print(new Request(['invoice_id' => '1234', 'terminal' => 0, 'user' => 0, 'route' => 'all']))->render();
        $this->assertStringNotContainsString('Customer request', $html);
        $this->assertStringNotContainsString('Refund please', $html);
    }

    public function test_missing_report_permission_is_rejected(): void
    {
        Auth::user()->is_super_admin = 0;
        DB::table('roles')->where('id', 1)->update(['permissions' => '[]']);
        $this->expectException(HttpException::class);
        $this->details();
    }

    public function test_invalid_invoice_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->details('1234%');
    }

    public function test_booking_seat_details_permission_can_use_lookup_and_print_with_existing_scope(): void
    {
        Auth::user()->is_super_admin = 0;
        DB::table('roles')->where('id', 1)->update(['permissions' => json_encode([['allow' => true, 'childs' => [
            ['name' => 'bookings', 'allow' => true, 'buttons' => [['name' => 'seat-details', 'allow' => true]]],
        ]]])]);
        DB::table('tickets')->where('id', 4)->update(['company_id' => 2]);
        $this->assertCount(3, $this->details()['tickets']);
        $this->assertStringContainsString('Activity history', app(TerminalTicketActivityController::class)->print(new Request(['invoice_id' => '1234']))->render());
        DB::table('roles')->where('id', 1)->update(['permissions' => json_encode([['allow' => true, 'childs' => [
            ['name' => 'bookings', 'allow' => true, 'buttons' => [['name' => 'seat-details', 'allow' => false]]],
        ]]])]);
        $this->expectException(HttpException::class);
        $this->details();
    }

    public function test_booking_drop_alert_uses_creation_time_and_excludes_reverted_records(): void
    {
        DB::table('roles')->where('id', 1)->update(['permissions' => json_encode([['allow' => true, 'childs' => [['name' => 'bookings', 'allow' => true, 'buttons' => []]]]])]);
        foreach ([
            'schedule_details' => ['company_id', 'schedule_id', 'departure_date', 'departure_id', 'destination_id', 'departure_time', 'schedule_date'],
            'drop_schedules' => ['company_id', 'schedule_id', 'schedule_date', 'is_drop', 'added_by', 'reason'],
        ] as $name => $columns) Schema::create($name, function (Blueprint $table) use ($columns) {
            $table->increments('id'); foreach ($columns as $column) $table->text($column)->nullable(); $table->timestamps(); $table->softDeletes();
        });
        DB::table('schedule_details')->insert(['id' => 1, 'company_id' => 1, 'schedule_id' => 7, 'departure_date' => '2026-09-28', 'departure_id' => 1, 'destination_id' => 2, 'departure_time' => '11:00:00', 'schedule_date' => '2026-09-28']);
        foreach ([1, 2] as $id) DB::table('drop_schedules')->insert(['id' => $id, 'company_id' => 1, 'schedule_id' => 7, 'schedule_date' => '2026-09-28', 'is_drop' => 1,
            'added_by' => 2, 'reason' => 'Operational delay', 'created_at' => $id === 1 ? '2026-09-26 08:00:00' : '2026-09-28 08:48:12',
            'updated_at' => '2026-09-28 09:00:00', 'deleted_at' => $id === 1 ? '2026-09-27 00:00:00' : null]);
        DB::table('users')->where('id', 2)->update(['deleted_at' => '2026-09-28 09:00:00']);
        $request = new Request(['id' => 7, 'date' => '2026-09-28', 'departureCity' => 1, 'destinationCity' => 2, 'departure_time' => '11:00 AM']);
        $controller = app(\App\Http\Controllers\Booking\BookingController::class);
        $drop = $controller->dropCheck($request)['checkDrop'];
        $this->assertEquals(2, $drop->id);
        $this->assertSame('28-Sep-2026 08:48 AM', $drop->dropped_at_display);
        $this->assertSame('Cancellation user', $drop->drop_by->name);
        $this->assertSame('Operational delay', $drop->reason);
        DB::table('drop_schedules')->where('id', 2)->update(['deleted_at' => '2026-09-28 10:00:00']);
        $this->assertNull($controller->dropCheck($request)['checkDrop']);
    }

    public function test_lookup_indexes_can_be_migrated_and_rolled_back_without_changing_records(): void
    {
        require_once database_path('migrations/2026_09_26_000001_add_ticket_activity_lookup_indexes.php');
        $migration = new \AddTicketActivityLookupIndexes();
        $migration->up();
        $this->assertCount(4, $this->details()['tickets']);
        $migration->down();
        $this->assertCount(4, $this->details()['tickets']);
    }
}
