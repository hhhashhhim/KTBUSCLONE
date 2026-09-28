<?php

namespace Tests\Feature;

use App\Http\Controllers\Report\TerminalSalesRouteGroupController;
use App\Models\TerminalSalesRouteGroup;
use App\Models\User;
use App\Support\TerminalSalesRouteGroups;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TerminalSalesRouteGroupsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'route_groups_test', 'database.connections.route_groups_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::setDefaultConnection('route_groups_test');
        Schema::clearResolvedInstance('db.schema');
        foreach (['users' => ['company_id', 'name', 'role_id', 'is_super_admin', 'route_ids'], 'roles' => ['permissions'], 'routes' => ['company_id', 'name', 'via', 'hide']] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->increments('id');
                foreach ($columns as $column) $table->text($column)->nullable();
                $table->timestamps(); $table->softDeletes();
            });
        }
        require_once database_path('migrations/2026_09_23_000001_create_terminal_sales_route_groups_table.php');
        (new \CreateTerminalSalesRouteGroupsTable)->up();
        $this->permissions(true);
        User::create(['id' => 1, 'company_id' => 1, 'role_id' => 1, 'name' => 'Test user', 'route_ids' => '[1]']);
        Auth::setUser(User::find(1));
        DB::table('routes')->insert([
            ['id' => 1, 'company_id' => 1, 'name' => 'Karachi - Rawalpindi', 'hide' => 0],
            ['id' => 2, 'company_id' => 1, 'name' => 'Rawalpindi - Karachi', 'hide' => 0],
            ['id' => 3, 'company_id' => 2, 'name' => 'Other company route', 'hide' => 0],
        ]);
    }

    private function permissions(bool $allowed): void
    {
        DB::table('roles')->updateOrInsert(['id' => 1], ['permissions' => json_encode([['allow' => true, 'childs' => [[
            'name' => 'terminal-sale', 'allow' => true, 'buttons' => [['name' => 'terminal-sale-route-filter', 'allow' => $allowed]],
        ]]]])]);
    }

    private function save(array $data): array
    {
        return app(TerminalSalesRouteGroupController::class)->save(new Request($data))->getData(true)['group'];
    }

    public function test_create_edit_list_and_resolve_for_screen_and_print(): void
    {
        $group = $this->save(['name' => ' Karachi closing ', 'route_ids' => ['1', '2']]);
        $this->assertSame('Karachi closing', $group['name']);
        $this->assertSame([1, 2], $group['route_ids']);
        $this->assertEquals(1, TerminalSalesRouteGroup::first()->created_by);
        $this->assertCount(1, app(TerminalSalesRouteGroupController::class)->index());
        // The shared resolver is called by filterData for both JSON and PDF.
        foreach ([['route' => [3]], ['route' => '3']] as $input) {
            $this->assertSame([1, 2], TerminalSalesRouteGroups::routeIds(new Request($input + ['route_group_id' => $group['id']])));
        }
        $updated = $this->save(['id' => $group['id'], 'name' => 'Karachi closing updated', 'route_ids' => [2]]);
        $this->assertEquals($group['id'], $updated['id']);
        $this->assertSame([2], TerminalSalesRouteGroups::routeIds(new Request(['route_group_id' => $group['id']])));
        $this->assertSame(1, TerminalSalesRouteGroup::count());
    }

    public function test_invalid_empty_duplicate_and_other_company_routes_are_rejected(): void
    {
        $this->save(['name' => 'Closing', 'route_ids' => [1]]);
        foreach ([['name' => '', 'route_ids' => [1]], ['name' => 'Empty', 'route_ids' => []], ['name' => 'Wrong company', 'route_ids' => [3]], ['name' => 'Closing', 'route_ids' => [2]], ['name' => 'Duplicates', 'route_ids' => [1, 1]]] as $input) {
            try { $this->save($input); $this->fail('Invalid group was accepted'); }
            catch (ValidationException $exception) { $this->assertNotEmpty($exception->errors()); }
        }
        $this->assertSame(1, TerminalSalesRouteGroup::count());
    }

    public function test_cross_company_groups_cannot_be_listed_used_or_edited(): void
    {
        $other = TerminalSalesRouteGroup::create(['company_id' => 2, 'name' => 'Private group', 'route_ids' => [3], 'created_by' => 2, 'updated_by' => 2]);
        $this->assertCount(0, app(TerminalSalesRouteGroupController::class)->index());
        try { TerminalSalesRouteGroups::routeIds(new Request(['route_group_id' => $other->id])); $this->fail('Cross-company group was resolved'); }
        catch (ModelNotFoundException $exception) { $this->assertTrue(true); }
        $this->expectException(ModelNotFoundException::class);
        $this->save(['id' => $other->id, 'name' => 'Changed', 'route_ids' => [1]]);
    }

    public function test_existing_route_filters_and_permission_restrictions_are_preserved(): void
    {
        $this->assertNull(TerminalSalesRouteGroups::routeIds(new Request(['route' => []])));
        $this->assertSame([1, 2], TerminalSalesRouteGroups::routeIds(new Request(['route' => '1,2'])));
        $group = $this->save(['name' => 'Closing', 'route_ids' => [1, 2]]);
        $this->permissions(false);
        $this->assertSame([1], TerminalSalesRouteGroups::routeIds(new Request(['route' => [2]])));
        try { $this->save(['name' => 'Forbidden', 'route_ids' => [1]]); $this->fail('Group write was allowed'); }
        catch (HttpException $exception) { $this->assertSame(403, $exception->getStatusCode()); }
        try { TerminalSalesRouteGroups::routeIds(new Request(['route_group_id' => $group['id']])); $this->fail('Group filter bypassed permission'); }
        catch (HttpException $exception) { $this->assertSame(403, $exception->getStatusCode()); }
    }

    public function test_deleted_routes_never_turn_group_into_unfiltered_report(): void
    {
        $group = $this->save(['name' => 'Closing', 'route_ids' => [1, 2]]);
        DB::table('routes')->whereIn('id', [1, 2])->update(['deleted_at' => now()]);
        $this->assertSame([], TerminalSalesRouteGroups::routeIds(new Request(['route_group_id' => $group['id']])));
    }

    public function test_print_marks_cancelled_invoice_even_when_status_column_is_hidden(): void
    {
        $ticket = (object) ['type' => 'canceled', 'invoice_id' => 954499, 'seat_fare' => 7000, 'discount' => 0,
            'terminal_id' => 1, 'bus_id' => 1, 'schedule_date' => '2026-09-23', 'schedule_time_exact' => '16:00:00',
            'schedule_time' => '16:00:00'];
        $html = view('reports.terminalSaleReport', ['tickets' => collect([$ticket]), 'visibleColumns' => ['invoice_id', 'transaction_id']])->render();
        $this->assertStringContainsString('954499', $html);
        $this->assertStringContainsString('<span class="cancelled-tag">Cancelled</span>', $html);
        $this->assertStringContainsString('N/A', $html);
        $ticket->type = 'booked';
        $html = view('reports.terminalSaleReport', ['tickets' => collect([$ticket]), 'visibleColumns' => ['invoice_id']])->render();
        $this->assertStringNotContainsString('<span class="cancelled-tag">Cancelled</span>', $html);
    }

    public function test_print_header_shows_dates_and_effective_terminal_even_without_results(): void
    {
        Schema::create('terminals', function (Blueprint $table) {
            $table->increments('id'); $table->integer('company_id'); $table->string('name'); $table->softDeletes();
        });
        DB::table('terminals')->insert(['id' => 1, 'company_id' => 1, 'name' => 'Karachi Main Terminal']);
        $controller = new class extends \App\Http\Controllers\TerminalController {
            public function filterData(Request $request) { return ['record' => collect()]; }
        };
        Auth::user()->is_super_admin = 1;
        $request = new Request(['terminal' => 1, 'fromDateTime' => '2026-09-01T00:00', 'toDateTime' => '2026-09-23T23:59']);
        $html = $controller->terminalSalesPdf($request)->render();
        $this->assertStringContainsString('Karachi Main Terminal', $html);
        $this->assertStringContainsString('01-Sep-2026 12:00 AM', $html);
        $this->assertStringContainsString('23-Sep-2026 11:59 PM', $html);
        $html = $controller->terminalSalesPdf(new Request(['terminal' => 0]))->render();
        $this->assertStringContainsString('All permitted terminals', $html);
        Auth::user()->is_super_admin = 0;
        Auth::user()->terminal_id = 1;
        $html = $controller->terminalSalesPdf(new Request(['terminal' => 999]))->render();
        $this->assertStringContainsString('Karachi Main Terminal', $html);
        $this->assertStringNotContainsString('Terminal unavailable', $html);
    }

    public function test_sales_and_print_separate_unpaid_reservations_without_inflating_refunds(): void
    {
        foreach ([
            'terminal_commissions' => ['terminal_id', 'route_id', 'fix_commission', 'flat_commission', 'percentage_commission', 'adjustment_commission'],
            'terminals' => ['company_id', 'name'],
            'customers' => ['name', 'cnic', 'contact'],
            'bus_classes' => ['name'],
            'buses' => ['bus_number'],
            'schedules' => ['name', 'time'],
            'booking_cancels' => ['ticket_id', 'percentage', 'type', 'reason'],
            'tickets' => ['company_id', 'terminal_id', 'route_id', 'bus_class_id', 'schedule_date', 'schedule_time', 'schedule_time_exact', 'updated_by', 'bus_id', 'invoice_id', 'seat_fare', 'discount', 'seat_no', 'customer_id', 'type', 'transaction_id', 'refund_amount'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->increments('id');
                foreach ($columns as $column) $table->text($column)->nullable();
                $table->timestamps(); $table->softDeletes();
            });
        }
        Auth::user()->is_super_admin = 1;
        DB::table('terminals')->insert(['id' => 1, 'company_id' => 1, 'name' => 'Test terminal']);
        DB::table('customers')->insert(['id' => 1, 'name' => 'Test passenger']);
        DB::table('terminal_commissions')->insert(['terminal_id' => 1, 'route_id' => 1, 'fix_commission' => 50, 'percentage_commission' => 10]);
        foreach ([
            [1, 'booked', 10000, 1000, null, null, null],
            [2, 'canceled', 7000, 0, 'booked', 10, 'Customer cancellation'],
            [3, 'canceled', 7000, 0, 'advance booking', 0, 'auto cancel'],
            [4, 'canceled', 9000, 0, 'advance booking', 20, 'Manual reservation cancellation'],
            [5, 'canceled', 1000, 0, 'booked', 0, 'Paid cash without transaction reference'],
        ] as [$id, $type, $fare, $discount, $previousType, $percentage, $reason]) {
            DB::table('tickets')->insert(['id' => $id, 'company_id' => 1, 'terminal_id' => 1, 'route_id' => 1, 'customer_id' => 1,
                'invoice_id' => 100 + $id, 'type' => $type, 'seat_fare' => $fare, 'discount' => $discount, 'seat_no' => $id,
                'schedule_date' => '2026-09-23', 'schedule_time' => '16:00:00', 'schedule_time_exact' => '16:00:00',
                'deleted_at' => $type === 'canceled' ? now() : null]);
            if ($previousType) DB::table('booking_cancels')->insert(['ticket_id' => $id, 'type' => $previousType, 'percentage' => $percentage, 'reason' => $reason]);
        }
        $report = app(\App\Http\Controllers\TerminalController::class)->filterData(new Request(['terminal' => 1]));
        $rows = $report['record']->keyBy('id');
        $this->assertCount(5, $rows);
        $this->assertSame('booked', $rows[1]->report_status);
        $this->assertSame('Cancelled', $rows[2]->report_status);
        $this->assertSame('Expired reservation', $rows[3]->report_status);
        $this->assertSame('Cancelled reservation', $rows[4]->report_status);
        foreach ([3, 4] as $id) {
            $this->assertTrue($rows[$id]->is_unpaid_cancellation);
            foreach (['sale', 'refund', 'cancellation_charges', 'seat_commission', 'terminal_commission', 'net_cash'] as $field) $this->assertEquals(0, $rows[$id]->$field);
        }
        $this->assertFalse($rows[5]->is_unpaid_cancellation, 'Missing transaction ID is not proof of an unpaid ticket');
        $this->assertEquals(1000, $rows[5]->refund);
        $this->assertEquals(9000, $report['total']['sale']);
        $this->assertEquals(7300, $report['total']['refund']);
        $this->assertEquals(700, $report['total']['cancellation_charges']);
        $this->assertEquals(8750, $report['total']['net_cash']);

        $html = view('reports.terminalSaleReport', ['tickets' => $report['record'],
            'visibleColumns' => ['invoice_id', 'status', 'sale', 'refund', 'cancellation_charges', 'net_cash']])->render();
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);
        foreach ([103 => 'Expired reservation', 104 => 'Cancelled reservation'] as $invoice => $label) {
            $cells = $xpath->query('//tbody/tr[td[1][contains(., "' . $invoice . '")]]/td');
            $this->assertSame(6, $cells->length);
            $this->assertStringContainsString($label, $cells->item(0)->textContent, 'Invoice badge remains visible when status is hidden');
            $this->assertSame($label, trim($cells->item(1)->textContent));
            for ($i = 2; $i < 6; $i++) $this->assertEquals(0, (float) trim($cells->item($i)->textContent));
        }
        $this->assertSame('booked', DB::table('tickets')->where('id', 1)->value('type'));
        $this->assertSame(4, DB::table('booking_cancels')->count());

        DB::table('tickets')->where('id', 2)->update(['refund_amount' => 6300]);
        DB::table('tickets')->where('id', 3)->update(['refund_amount' => 999]); // Bad legacy value must not make an unpaid hold a refund.
        $overissue = (array) DB::table('tickets')->where('id', 1)->first();
        DB::table('tickets')->insert(array_merge($overissue, ['id' => 6, 'invoice_id' => 106, 'type' => 'over-issue', 'seat_fare' => 2000, 'discount' => 0]));
        DB::table('tickets')->insert(array_merge($overissue, ['id' => 7, 'invoice_id' => 107, 'type' => 'canceled', 'seat_fare' => 0, 'discount' => 0]));
        DB::table('booking_cancels')->insert(['ticket_id' => 7, 'type' => 'booked', 'percentage' => 0]);
        $controller = app(\App\Http\Controllers\TerminalController::class);
        foreach ([
            [['ticket_status' => 'booked'], [1]],
            [['ticket_status' => 'over-issue'], [6]],
            [['ticket_status' => 'canceled'], [2, 3, 4, 5, 7]],
            [['ticket_status' => 'refunded'], [2]],
            [['ticket_status' => 'unpaid', 'cancellation_scope' => 'financial'], [3, 4]],
            [['cancellation_scope' => 'financial'], [1, 2, 5, 6]],
            [['cancellation_scope' => 'charges'], [1, 2, 6]],
            [['cancellation_scope' => 'refund'], [1, 2, 6]],
            [['ticket_status' => 'canceled', 'cancellation_scope' => 'refund'], [2]],
            [['cancellation_scope' => 'all'], [1, 2, 3, 4, 5, 6, 7]],
        ] as [$filters, $expected]) {
            $filtered = $controller->filterData(new Request($filters + ['terminal' => 1]));
            $this->assertSame($expected, $filtered['record']->pluck('id')->sort()->values()->all(), json_encode($filters));
        }
        $request = new Request(['terminal' => 1, 'ticket_status' => 'canceled', 'cancellation_scope' => 'refund']);
        $filtered = $controller->filterData($request);
        $this->assertEquals(0, $filtered['total']['sale']);
        $this->assertEquals(6300, $filtered['total']['refund']);
        $this->assertEquals(700, $filtered['total']['cancellation_charges']);
        $html = $controller->terminalSalesPdf($request)->render();
        $this->assertStringContainsString('Cancellations with a recorded refund only', $html);
        $this->assertStringNotContainsString('Expired reservation</span>', $html);
        $this->assertEquals(10550, $controller->filterData(new Request(['terminal' => 1, 'cancellation_scope' => 'financial']))['total']['net_cash']);
    }

    public function test_invalid_sales_status_filter_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        \App\Support\TerminalSaleFilters::validate(new Request(['ticket_status' => 'invalid']));
    }
}
