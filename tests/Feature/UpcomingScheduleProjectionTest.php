<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\TicketingApiController;
use App\Models\Schedule\ScheduleDetail;
use App\Models\User;
use App\Services\UpcomingScheduleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Opt-in MySQL integration tests. Only a uniquely named disposable database is modified. */
class UpcomingScheduleProjectionTest extends TestCase
{
    private $database;
    private $originalConnection;
    private $migration;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('RUN_SCHEDULE_PROJECTION_MYSQL_TESTS') !== '1') {
            $this->markTestSkipped('Set RUN_SCHEDULE_PROJECTION_MYSQL_TESTS=1 to test using a disposable local MySQL database.');
        }
        $this->originalConnection = config('database.default');
        $config = config('database.connections.' . $this->originalConnection);
        if ($config['driver'] !== 'mysql' || !in_array($config['host'], ['127.0.0.1', 'localhost', '::1'], true) || !empty($config['url'])) {
            $this->markTestSkipped('These tests require a local MySQL connection.');
        }
        $this->database = 'kt_schedule_projection_test_' . bin2hex(random_bytes(6));
        DB::connection($this->originalConnection)->statement('CREATE DATABASE `' . $this->database . '`');
        $config['database'] = $this->database;
        config(['database.connections.projection_test' => $config]);
        DB::setDefaultConnection('projection_test');
        Schema::clearResolvedInstance('db.schema');
        $this->createFixtures();
        require_once database_path('migrations/2026_09_18_000001_create_schedule_details_next_60_days.php');
        $this->migration = new \CreateScheduleDetailsNext60Days();
    }

    protected function tearDown(): void
    {
        if ($this->database && preg_match('/^kt_schedule_projection_test_[a-f0-9]{12}$/', $this->database)) {
            DB::disconnect('projection_test');
            DB::setDefaultConnection($this->originalConnection);
            Schema::clearResolvedInstance('db.schema');
            DB::connection($this->originalConnection)->statement('DROP DATABASE `' . $this->database . '`');
        }
        parent::tearDown();
    }

    private function table($name, array $integers = [], array $strings = [], array $dates = [])
    {
        Schema::create($name, function (Blueprint $table) use ($integers, $strings, $dates) {
            $table->bigIncrements('id');
            foreach ($integers as $column) { $table->integer($column)->nullable(); }
            foreach ($strings as $column) { $table->text($column)->nullable(); }
            foreach ($dates as $column) { $table->date($column)->nullable(); }
            $table->softDeletes();
            $table->timestamps();
        });
    }

    private function createFixtures()
    {
        $this->table('schedule_details', ['company_id', 'schedule_id', 'departure_id', 'destination_id', 'bus_class_id'], ['departure_time', 'extra_fixture_field'], ['schedule_date', 'departure_date']);
        $this->table('schedules', ['company_id', 'route_id', 'bus_class_id', 'discount_id', 'surcharge_id', 'hide'], ['name', 'time']);
        $this->table('cities', [], ['name']);
        $this->table('companies', [], ['name']);
        $this->table('customers', [], ['name']);
        $this->table('users', [], ['name']);
        $this->table('bus_classes', [], ['name', 'front_icons', 'seat_map']);
        $this->table('terminals', ['company_id', 'advance_booking'], ['name', 'available_seats']);
        $this->table('schedule_terminal_visibilities', ['company_id', 'terminal_id', 'schedule_id', 'visibility']);
        $this->table('drop_schedules', ['company_id', 'schedule_id'], [], ['schedule_date']);
        $this->table('terminal_visibilities', ['route_id', 'departure_city_id', 'destination_city_id', 'online_visibilty']);
        $this->table('terminal_time_differences', ['company_id', 'city_id', 'route_id', 'terminal_id', 'show', 'time_difference'], ['display_name']);
        $this->table('fare_classes', ['company_id', 'is_active'], ['name', 'color']);
        $this->table('fare_tables', ['company_id', 'from_city_id', 'to_city_id', 'fare_class', 'fare'], ['time_difference']);
        $this->table('surcharges', ['is_active', 'percentage', 'flat'], ['type']);
        $this->table('discounts', ['is_active', 'percentage', 'flat'], ['type']);
        $this->table('schedule_terminal_discounts', ['discount_id', 'terminal_id']);
        $this->table('terminal_discounts', ['terminal_id', 'route_id', 'discount'], [], ['start_date', 'end_date']);
        $this->table('routes', ['online_seats'], ['name', 'online_seat_choices']);
        $this->table('routes_fares', ['route_id', 'departure_city_id', 'destination_city_id']);
        $this->table('route_online_terminals', ['route_id', 'company_id', 'terminal_id']);
        $this->table('limited_seats', ['company_id', 'route_id', 'departure_city_id', 'destination_city_id', 'limited_seat']);
        $this->table('tickets', ['company_id', 'schedule_id', 'departure_city_id', 'destination_city_id', 'is_partial', 'online_terminal'], ['seat_no', 'gender', 'type'], ['schedule_date']);
        DB::table('companies')->insert([['id' => 1, 'name' => 'Company 1'], ['id' => 2, 'name' => 'Company 2']]);
        DB::table('cities')->insert([['id' => 1, 'name' => 'Origin'], ['id' => 2, 'name' => 'Destination']]);
        DB::table('bus_classes')->insert(['id' => 1, 'name' => 'Test class', 'front_icons' => '[]', 'seat_map' => json_encode([[
            ['reserved' => true, 'type' => 0, 'class' => 1, 'seatNo' => '1'],
            ['reserved' => true, 'type' => 0, 'class' => 1, 'seatNo' => '2'],
        ]])]);
        DB::table('schedules')->insert(['id' => 1, 'company_id' => 1, 'route_id' => 1, 'bus_class_id' => 1, 'discount_id' => 1, 'surcharge_id' => 1, 'hide' => 0, 'name' => 'Test schedule', 'time' => '18:00:00']);
        DB::table('terminals')->insert(['id' => 1, 'company_id' => 1, 'advance_booking' => 90, 'name' => 'Test terminal']);
        DB::table('schedule_terminal_visibilities')->insert(['company_id' => 1, 'terminal_id' => 1, 'schedule_id' => 1, 'visibility' => 1]);
        DB::table('routes')->insert(['id' => 1, 'name' => 'Test route', 'online_seats' => 20]);
        DB::table('routes_fares')->insert(['route_id' => 1, 'departure_city_id' => 1, 'destination_city_id' => 2]);
        DB::table('fare_classes')->insert(['id' => 1, 'company_id' => 1, 'name' => 'Executive', 'color' => '#00ff00', 'is_active' => 1]);
        DB::table('fare_tables')->insert(['company_id' => 1, 'from_city_id' => 1, 'to_city_id' => 2, 'fare_class' => 1, 'fare' => 1000, 'time_difference' => '04:30']);
        DB::table('surcharges')->insert(['id' => 1, 'is_active' => 1, 'type' => 'flat', 'flat' => 100]);
        DB::table('discounts')->insert(['id' => 1, 'is_active' => 1, 'type' => 'percentage', 'percentage' => 10]);
        DB::table('schedule_terminal_discounts')->insert(['discount_id' => 1, 'terminal_id' => 1]);
        Auth::setUser((new User())->forceFill(['id' => 1, 'company_id' => 1, 'terminal_id' => 1]));
        $this->detail(1, 1);
    }

    private function detail($id, $days, $companyId = 1)
    {
        DB::table('schedule_details')->insert([
            'id' => $id, 'company_id' => $companyId, 'schedule_id' => 1, 'departure_id' => 1,
            'destination_id' => 2, 'bus_class_id' => 1, 'departure_time' => '18:00:00',
            'schedule_date' => today()->addDays($days)->toDateString(),
            'departure_date' => today()->addDays($days)->toDateString(), 'extra_fixture_field' => 'preserve me',
        ]);
    }

    public function test_copy_lifecycle_and_original_history_are_preserved()
    {
        $service = new UpcomingScheduleService();
        $date = today()->addDay()->toDateString();
        $this->assertSame('schedule_details', $service->query($date, 1)->getModel()->getTable());
        $this->migration->up();
        $this->assertSame('schedule_details', $service->query($date, 1)->getModel()->getTable());
        $this->detail(2, -1);
        $this->detail(3, 59);
        $this->detail(4, 60);
        $this->detail(5, 1, 2);
        $this->detail(6, 0);
        DB::table('schedule_details')->where('id', 6)->update(['schedule_date' => today()->subDay()->toDateString()]);
        $original = DB::table('schedule_details')->orderBy('id')->get()->toJson();
        $service->sync(1);
        $this->assertSame($original, DB::table('schedule_details')->orderBy('id')->get()->toJson());
        $this->assertSame([1, 3, 6], DB::table(UpcomingScheduleService::TABLE)->where('company_id', 1)->orderBy('id')->pluck('id')->map(function ($id) { return (int) $id; })->all());
        $this->assertEquals(DB::table('schedule_details')->where('id', 1)->first(), DB::table(UpcomingScheduleService::TABLE)->where('id', 1)->first());
        $this->assertSame(UpcomingScheduleService::TABLE, $service->query($date, 1)->getModel()->getTable());
        $this->assertSame('schedule_details', $service->query($date, 2)->getModel()->getTable());
        $this->assertSame('schedule_details', $service->query(today()->addDays(60)->toDateString(), 1)->getModel()->getTable());
        $this->assertSame('schedule_details', $service->query(today()->subDay()->toDateString(), 1)->getModel()->getTable());

        // Query-builder bulk writes bypass Eloquent events but must still mirror atomically.
        DB::beginTransaction();
        DB::table('schedule_details')->whereIn('id', [1, 3])->update(['bus_class_id' => 9]);
        $this->assertSame(2, DB::table(UpcomingScheduleService::TABLE)->where('bus_class_id', 9)->count());
        DB::rollBack();
        $this->assertSame(0, DB::table(UpcomingScheduleService::TABLE)->where('bus_class_id', 9)->count());
        $this->detail(7, 2);
        $this->assertTrue(DB::table(UpcomingScheduleService::TABLE)->where('id', 7)->exists());
        DB::table('schedule_details')->where('id', 7)->update(['departure_time' => '17:15:00', 'bus_class_id' => 2]);
        $this->assertEquals(DB::table('schedule_details')->where('id', 7)->first(), DB::table(UpcomingScheduleService::TABLE)->where('id', 7)->first());
        DB::table('schedule_details')->where('id', 7)->update(['deleted_at' => now()]);
        $this->assertNull($service->query(today()->addDays(2)->toDateString(), 1)->find(7));
        DB::table('schedule_details')->where('id', 7)->update(['deleted_at' => null]);
        $this->assertNotNull($service->query(today()->addDays(2)->toDateString(), 1)->find(7));
        DB::table('schedule_details')->where('id', 7)->update(['departure_date' => today()->addDays(60)->toDateString()]);
        $this->assertFalse(DB::table(UpcomingScheduleService::TABLE)->where('id', 7)->exists());
        DB::table('schedule_details')->where('id', 7)->update(['departure_date' => $date]);
        $this->assertTrue(DB::table(UpcomingScheduleService::TABLE)->where('id', 7)->exists());
        DB::table('schedule_details')->where('id', 7)->delete();
        $this->assertFalse(DB::table(UpcomingScheduleService::TABLE)->where('id', 7)->exists());

        DB::table(UpcomingScheduleService::TABLE)->where('id', 1)->delete();
        DB::table(UpcomingScheduleService::TABLE)->where('id', 3)->update(['extra_fixture_field' => 'outdated']);
        $orphan = (array) DB::table('schedule_details')->where('id', 1)->first();
        $orphan['id'] = 999;
        DB::table(UpcomingScheduleService::TABLE)->insert($orphan);
        $service->sync(1);
        $this->assertTrue(DB::table(UpcomingScheduleService::TABLE)->where('id', 1)->exists());
        $this->assertSame('preserve me', DB::table(UpcomingScheduleService::TABLE)->where('id', 3)->value('extra_fixture_field'));
        $this->assertFalse(DB::table(UpcomingScheduleService::TABLE)->where('id', 999)->exists());
        $service->sync(2);
        $this->assertSame(UpcomingScheduleService::TABLE, $service->query($date, 2)->getModel()->getTable());
        // A stale window must fall back until the daily/manual refresh extends it.
        DB::table('schedule_projection_syncs')->where('company_id', 1)->update(['window_end' => today()->toDateString()]);
        $this->assertSame('schedule_details', $service->query($date, 1)->getModel()->getTable());
        \Carbon\Carbon::setTestNow(today()->addDay());
        try {
            $service->sync(1);
            $this->assertTrue(DB::table(UpcomingScheduleService::TABLE)->where('id', 4)->exists());
            $this->assertFalse(DB::table(UpcomingScheduleService::TABLE)->where('id', 6)->exists());
            $this->assertTrue(DB::table('schedule_details')->where('id', 6)->exists());
        } finally {
            \Carbon\Carbon::setTestNow();
        }
        $original = DB::table('schedule_details')->orderBy('id')->get()->toJson();
        $this->migration->down();
        $this->assertSame($original, DB::table('schedule_details')->orderBy('id')->get()->toJson());
        $this->assertSame('schedule_details', $service->query($date, 1)->getModel()->getTable());
    }

    private function responseFor($class, $method, $date)
    {
        $request = Request::create('/test', 'POST', ['departure_city_id' => 1, 'destination_city_id' => 2, 'date' => $date, 'schedule_id' => 1, 'departure_time' => '18:00:00']);
        $this->app->instance('request', $request);
        $result = (new $class())->$method($request);
        if ($result instanceof \App\Http\Resources\BreakResource) {
            $this->fail($class . '::' . $method . ': ' . $result->resource);
        }
        $response = $result instanceof \Illuminate\Contracts\Support\Responsable ? $result->toResponse($request) : $result;
        return json_decode($response->getContent(), true);
    }

    public function test_both_external_apis_keep_responses_live_and_exclude_dropped_or_hidden_trips()
    {
        $date = today()->addDay()->toDateString();
        $baseline = [];
        foreach ([BookingApiController::class, TicketingApiController::class] as $class) {
            foreach (['availableSchedules', 'previewSchedule'] as $method) {
                $baseline[$class][$method] = $this->responseFor($class, $method, $date);
                $this->assertSame('success', $baseline[$class][$method]['status']);
            }
        }
        $this->migration->up();
        (new UpcomingScheduleService())->sync(1);
        foreach ($baseline as $class => $methods) {
            foreach ($methods as $method => $response) {
                $this->assertSame($response, $this->responseFor($class, $method, $date));
            }
        }

        // Booking changes are visible without syncing the schedule copy again.
        DB::table('tickets')->insert(['company_id' => 1, 'schedule_id' => 1, 'schedule_date' => $date, 'departure_city_id' => 1, 'destination_city_id' => 2, 'seat_no' => '1', 'gender' => 'male', 'type' => 'booked', 'is_partial' => 0, 'online_terminal' => 1]);
        $booked = $this->responseFor(BookingApiController::class, 'previewSchedule', $date);
        $this->assertSame('booked', $booked['data']['seat_map'][0][0]['type']);
        $available = $this->responseFor(BookingApiController::class, 'availableSchedules', $date);
        $this->assertSame(1, $available['data'][0]['available_seats']);
        DB::table('drop_schedules')->insert(['company_id' => 1, 'schedule_id' => 1, 'schedule_date' => $date]);
        foreach (array_keys($baseline) as $class) {
            foreach (['availableSchedules', 'previewSchedule'] as $method) {
                $this->assertSame('empty', $this->responseFor($class, $method, $date)['status']);
            }
        }
        DB::table('drop_schedules')->delete();
        DB::table('schedules')->where('id', 1)->update(['hide' => 1]);
        foreach (array_keys($baseline) as $class) {
            foreach (['availableSchedules', 'previewSchedule'] as $method) {
                $this->assertSame('empty', $this->responseFor($class, $method, $date)['status']);
            }
        }
    }
}
