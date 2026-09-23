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
}
