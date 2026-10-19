<?php

namespace Tests\Feature;

use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Schedule\ScheduleController;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ScheduleQuotaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ScheduleQuotaTest extends TestCase
{
    private $originalConnection;
    private $database;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = config('database.default');
        $config = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        if (getenv('RUN_SCHEDULE_QUOTA_MYSQL_TESTS') === '1') {
            $config = config('database.connections.' . $this->originalConnection);
            if ($config['driver'] !== 'mysql' || !in_array($config['host'], ['localhost', '127.0.0.1', '::1'], true) || !empty($config['url'])) {
                $this->markTestSkipped('Only a local MySQL connection may be used.');
            }
            $this->database = 'kt_quota_test_' . bin2hex(random_bytes(6));
            DB::connection($this->originalConnection)->statement('CREATE DATABASE `' . $this->database . '`');
            $config['database'] = $this->database;
        }
        config(['database.connections.quota_test' => $config, 'database.connections.quota_second' => $config]);
        DB::purge('quota_test');
        DB::setDefaultConnection('quota_test');
        Schema::clearResolvedInstance('db.schema');
        Carbon::setTestNow(Carbon::parse('2026-10-20 18:00:00'));
        $this->table('schedules', ['company_id', 'route_id', 'updated_by'], ['time']);
        require_once database_path('migrations/2026_10_16_000001_add_intermediate_quota_to_schedules.php');
        (new \AddIntermediateQuotaToSchedules())->up();
        $this->table('routes');
        $this->table('routes_fares', ['route_id', 'departure_city_id', 'destination_city_id']);
        $this->table('schedule_details', ['company_id', 'schedule_id', 'departure_id', 'destination_id'], ['schedule_date', 'departure_date', 'departure_time']);
        $this->table('tickets', ['company_id', 'schedule_id', 'terminal_id', 'departure_city_id', 'destination_city_id', 'schedule_details_id'], ['schedule_date', 'type', 'seat_no', 'booked_time']);
        $this->table('roles', [], ['permissions']);
        $this->table('activity_logs', ['company_id', 'activity_by'], ['message', 'requested_host']);
        DB::table('schedules')->insert(['id' => 1, 'company_id' => 1, 'route_id' => 1, 'time' => '23:00:00', 'intermediate_quota' => 2]);
        DB::table('routes')->insert(['id' => 1]);
        DB::table('routes_fares')->insert([
            ['route_id' => 1, 'departure_city_id' => 10, 'destination_city_id' => 20],
            ['route_id' => 1, 'departure_city_id' => 20, 'destination_city_id' => 30],
        ]);
        $this->detail(1, 10, 30, '2026-10-20', '23:00:00');
        $this->detail(2, 10, 20, '2026-10-20', '23:00:00');
        $this->detail(3, 20, 30, '2026-10-21', '01:00:00');
        DB::table('roles')->insert(['id' => 1, 'permissions' => json_encode([['allow' => true, 'childs' => [
            ['name' => 'bookings', 'allow' => true, 'buttons' => [['name' => 'reschedule-seats', 'allow' => true], ['name' => 'overissue-seat', 'allow' => true]]],
            ['name' => 'schedules', 'allow' => true, 'buttons' => [['name' => 'edit-schedule', 'allow' => true]]],
        ]]])]);
        Auth::setUser((new User())->forceFill(['id' => 1, 'name' => 'Quota test', 'role_id' => 1, 'company_id' => 1, 'terminal_id' => 1]));
        $this->service = new ScheduleQuotaService();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('quota_test');
        DB::purge('quota_second');
        if ($this->originalConnection) {
            DB::setDefaultConnection($this->originalConnection);
            Schema::clearResolvedInstance('db.schema');
        }
        if ($this->database && preg_match('/^kt_quota_test_[a-f0-9]{12}$/', $this->database)) {
            DB::connection($this->originalConnection)->statement('DROP DATABASE `' . $this->database . '`');
        }
        parent::tearDown();
    }

    private function table($name, array $ints = [], array $strings = [])
    {
        Schema::create($name, function (Blueprint $table) use ($ints, $strings) {
            $table->bigIncrements('id');
            foreach ($ints as $column) { $table->integer($column)->nullable(); }
            foreach ($strings as $column) { $table->string($column, 2000)->nullable(); }
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function detail($id, $from, $to, $date, $time)
    {
        DB::table('schedule_details')->insert(['id' => $id, 'company_id' => 1, 'schedule_id' => 1,
            'schedule_date' => '2026-10-20', 'departure_date' => $date, 'departure_time' => $time,
            'departure_id' => $from, 'destination_id' => $to]);
    }

    private function ticket(array $overrides = [])
    {
        return Ticket::create(array_merge(['company_id' => 1, 'schedule_id' => 1, 'schedule_date' => '2026-10-20',
            'terminal_id' => 1, 'departure_city_id' => 10, 'destination_city_id' => 20,
            'seat_no' => '1-DN', 'type' => 'booked', 'booked_time' => '2026-10-20 17:00:00'], $overrides));
    }

    private function guard($detailId, $count, array $excluded = [])
    {
        $seats = is_array($count) ? $count : ($count ? array_map(function ($i) { return 'NEW-' . $i; }, range(1, $count)) : []);
        return DB::transaction(function () use ($detailId, $seats, $excluded) {
            return $this->service->assertAvailable(ScheduleDetail::findOrFail($detailId), $seats, $excluded);
        });
    }

    private function rejects($detailId, $count, array $excluded = [])
    {
        try {
            $this->guard($detailId, $count, $excluded);
            $this->fail('An over-quota booking was accepted.');
        } catch (ValidationException $e) {
            $this->assertSame('Quota for midway routes is completed.', $e->errors()['quota'][0]);
        }
    }

    public function test_same_seats_on_onward_segments_count_once_across_terminals()
    {
        $this->ticket();
        $this->ticket(['seat_no' => '2']);
        $this->assertSame(2, $this->guard(3, [' 1-dn ', '2'])['limit']);
        $this->ticket(['terminal_id' => 99, 'departure_city_id' => 20, 'destination_city_id' => 30, 'seat_no' => ' 1-dn ']);
        $this->ticket(['terminal_id' => 99, 'departure_city_id' => 20, 'destination_city_id' => 30, 'seat_no' => '2']);
        $preview = $this->service->preview(ScheduleDetail::find(3), Ticket::all(), []);
        $this->assertSame(2, $preview['quota']['used']);
        $this->assertSame(0, $preview['quota']['remaining']);
        $this->assertSame(2, $this->guard(3, ['1-DN', '2'])['limit']);
        $this->rejects(2, 1);
        $this->rejects(3, 1);
        $this->rejects(3, ['1-DN', '3']);
        $this->assertFalse($this->guard(1, 40)['intermediate']);
    }

    public function test_seat_is_released_only_after_its_last_qualifying_ticket_is_removed()
    {
        DB::table('schedules')->update(['intermediate_quota' => 1]);
        $first = $this->ticket();
        $onward = $this->ticket(['departure_city_id' => 20, 'destination_city_id' => 30]);
        $first->delete();
        $this->rejects(2, ['2']);
        $this->assertSame(1, $this->guard(3, ['1-DN'])['limit']);
        $onward->delete();
        $this->assertSame(1, $this->guard(2, ['2'])['limit']);
    }

    public function test_rescheduling_checks_replacement_seats_not_old_seat_numbers()
    {
        DB::table('schedules')->update(['intermediate_quota' => 1]);
        $first = $this->ticket();
        $onward = $this->ticket(['departure_city_id' => 20, 'destination_city_id' => 30]);
        $this->assertSame(1, $this->guard(3, ['1-DN'], [$onward->id])['limit']);
        $this->rejects(3, ['2'], [$onward->id]);
        $this->assertSame(1, $this->guard(3, ['2'], [$first->id, $onward->id])['limit']);
    }

    public function test_departure_summary_counts_booked_passenger_journeys_not_unique_seats()
    {
        $this->ticket();
        $this->ticket(['seat_no' => '2']);
        $this->ticket(['departure_city_id' => 20, 'destination_city_id' => 30]);
        $this->ticket(['seat_no' => '2', 'departure_city_id' => 20, 'destination_city_id' => 30]);
        $this->ticket(['type' => 'advance booking']);
        $this->ticket(['type' => 'canceled']);
        $this->ticket(['deleted_at' => now()]);
        $tickets = Ticket::withTrashed()->get()->each(function ($ticket) {
            $ticket->setRelation('departure_city', (new \App\Models\City())->forceFill(['name' => $ticket->departure_city_id == 10 ? 'Karachi' : 'Multan']));
        });
        $summary = (new \App\Services\PassengerSummaryService())->byDepartureCity($tickets);
        $this->assertSame(['Karachi', 'Multan'], array_column($summary, 'city_name'));
        $this->assertSame([2, 2], array_column($summary, 'passengers'));
        $this->assertSame([], (new \App\Services\PassengerSummaryService())->byDepartureCity(collect()));
    }

    public function test_only_active_paid_intermediate_tickets_for_this_company_and_departure_count()
    {
        $this->ticket(['type' => 'advance booking']);
        $this->ticket(['type' => 'canceled']);
        $this->ticket(['deleted_at' => now()]);
        $this->ticket(['company_id' => 2]);
        $this->ticket(['schedule_id' => 2]);
        $this->ticket(['schedule_date' => '2026-10-21']);
        $this->ticket(['destination_city_id' => 30]);
        $this->assertSame(2, $this->guard(2, 2)['limit']);
        $this->ticket();
        $this->rejects(2, 2);
        $this->assertSame(2, $this->guard(2, 1)['limit']);
    }

    public function test_zero_unlimited_and_unpaid_bookings()
    {
        DB::table('schedules')->update(['intermediate_quota' => 0]);
        $this->rejects(2, 1);
        $this->assertSame(0, $this->guard(2, 0)['limit']);
        $this->assertFalse($this->guard(1, 40)['intermediate']);
        DB::table('schedules')->update(['intermediate_quota' => null]);
        $this->assertNull($this->guard(2, 40)['limit']);
    }

    public function test_limit_lifts_at_main_departure_even_when_onward_city_is_next_day()
    {
        DB::table('schedules')->update(['intermediate_quota' => 0]);
        Carbon::setTestNow('2026-10-20 22:59:59');
        $this->rejects(3, 1);
        Carbon::setTestNow('2026-10-20 23:00:00');
        $this->assertTrue($this->guard(3, 20)['departed']);
        Carbon::setTestNow('2026-10-21 00:30:00');
        $this->assertTrue($this->guard(3, 20)['departed']);
    }

    public function test_cancellation_releases_capacity_and_reschedule_does_not_double_count()
    {
        $first = $this->ticket();
        $second = $this->ticket(['seat_no' => '2']);
        $this->rejects(2, 1);
        $this->assertSame(2, $this->guard(3, 2, [$first->id, $second->id])['limit']);
        $this->rejects(3, 2, [$first->id]);
        $second->delete();
        $this->assertSame(2, $this->guard(2, 1)['limit']);
    }

    public function test_marker_only_highlights_free_onward_seats_and_preserves_existing_status()
    {
        $this->ticket();
        $this->ticket(['seat_no' => '2']);
        $this->ticket(['seat_no' => '2', 'departure_city_id' => 20, 'destination_city_id' => 30, 'type' => 'advance booking']);
        $this->ticket(['seat_no' => '3', 'type' => 'advance booking']);
        $this->ticket(['seat_no' => '4', 'booked_time' => '2026-10-20 23:01:00']);
        $this->ticket(['seat_no' => '5']);
        $this->ticket(['seat_no' => '6']);
        $this->ticket(['seat_no' => '7']);
        $map = [[['seatNo' => ' 1-dn '], ['seatNo' => '2'], ['seatNo' => '3'], ['seatNo' => '4'],
            ['seatNo' => '5', 'type' => 'not_for_sale'], ['seatNo' => '6', 'type' => 'booked', 'gender' => 0],
            ['seatNo' => '7', 'selected' => true], ['driver' => true]]];
        $preview = $this->service->preview(ScheduleDetail::find(3), Ticket::all(), $map);
        $this->assertSame([true, false, false, false, false, false, true, false], array_column($preview['seat_map'][0], 'quota_reuse'));
        $this->assertSame('booked', $preview['seat_map'][0][5]['type']);
        $this->assertTrue($preview['seat_map'][0][6]['selected']); // UI selection colour takes priority.
        $this->assertSame(5, $preview['quota']['used']);
        $before = $this->service->preview(ScheduleDetail::find(2), Ticket::all(), [[['seatNo' => '1-DN']]]);
        $this->assertFalse($before['seat_map'][0][0]['quota_reuse']);
    }

    public function test_middle_segment_marks_free_seat_pink_before_and_after_but_never_overlapping()
    {
        // RWP (10) -> FSD (20) -> Multan (30) -> Karachi (40).
        DB::table('routes_fares')->insert(['route_id' => 1, 'departure_city_id' => 30, 'destination_city_id' => 40]);
        DB::table('schedule_details')->where('id', 1)->update(['destination_id' => 40]);
        $this->detail(4, 30, 40, '2026-10-21', '05:00:00');
        $this->detail(5, 10, 30, '2026-10-20', '23:00:00');
        $this->detail(6, 20, 40, '2026-10-21', '01:00:00');
        $this->ticket(['departure_city_id' => 20, 'destination_city_id' => 30]);
        $map = [[['seatNo' => ' 1-dn ']]];
        foreach ([2 => true, 4 => true, 1 => false, 3 => false, 5 => false, 6 => false] as $detailId => $expected) {
            $preview = $this->service->preview(ScheduleDetail::find($detailId), Ticket::all(), $map);
            $this->assertSame($expected, $preview['seat_map'][0][0]['quota_reuse'], 'Selected segment ' . $detailId);
            $this->assertSame(1, $preview['quota']['used']);
        }
        // An earlier reservation still blocks the seat, even if the paid midway ticket allows reuse.
        $this->ticket(['type' => 'advance booking']);
        $before = $this->service->preview(ScheduleDetail::find(2), Ticket::all(), $map);
        $this->assertFalse($before['seat_map'][0][0]['quota_reuse']);
        $after = $this->service->preview(ScheduleDetail::find(4), Ticket::all(), $map);
        $this->assertTrue($after['seat_map'][0][0]['quota_reuse']);
    }

    public function test_paid_booking_and_reservation_confirmation_return_clear_quota_error_without_writes()
    {
        DB::table('schedules')->update(['intermediate_quota' => 0]);
        $reserved = $this->ticket(['type' => 'advance booking']);
        foreach ([0, 1] as $flag) {
            Cache::flush();
            $request = Request::create('/', 'POST', ['departureCity' => 10, 'destinationCity' => 20, 'schedule' => 1,
                'date' => '2026-10-20', 'departure_time' => '23:00:00', 'terminalId' => 1,
                'flag' => $flag, 'type' => 'booked', 'selectedSeats' => ['1'], 'alreadyBookedId' => [$reserved->id]]);
            $response = app(BookingController::class)->store($request);
            $this->assertSame(422, $response->status());
            $this->assertArrayHasKey('quota', $response->getData(true)['errors'], $response->getContent());
            $this->assertSame('Quota for midway routes is completed.', $response->getData(true)['errors']['quota'][0]);
            $this->assertSame(0, DB::transactionLevel());
            $this->assertSame('advance booking', $reserved->fresh()->type);
        }
    }

    public function test_rescheduling_into_full_quota_is_rejected_using_saved_ticket_type()
    {
        Cache::flush();
        DB::table('schedules')->update(['intermediate_quota' => 0]);
        $old = $this->ticket(['schedule_id' => 2]);
        $response = app(BookingController::class)->store(Request::create('/', 'POST', ['rc_flag' => 1, 'data' => [[
            'dataAll' => ['id' => $old->id, 'type' => 'advance booking'], // client type cannot bypass the paid quota
            'dataDepartureCity' => 10, 'dataDestination' => 20, 'rescheduleDate' => '2026-10-20',
            'rescheduleSchedule' => 1, 'newDepartureTime' => 1, 'departure_time' => '23:00:00',
            'selected_seatNo' => '1-DN',
        ]]]));
        $this->assertSame(422, $response->status());
        $this->assertSame('Quota for midway routes is completed.', $response->getData(true)['errors']['quota'][0]);
        $this->assertSame(0, DB::transactionLevel());
        $this->assertNotNull($old->fresh());
    }

    public function test_setting_is_validated_company_scoped_and_logged()
    {
        $controller = app(ScheduleController::class);
        foreach ([10, 0, null] as $limit) {
            $response = $controller->updateQuota(Request::create('/', 'POST', ['id' => 1, 'intermediate_quota' => $limit]));
            $this->assertSame($limit, $response->getData(true)['intermediate_quota']);
        }
        $this->assertSame(3, DB::table('activity_logs')->count());
        foreach ([-1, 1.5, 100001] as $bad) {
            try {
                $controller->updateQuota(Request::create('/', 'POST', ['id' => 1, 'intermediate_quota' => $bad]));
                $this->fail('Invalid quota accepted');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('intermediate_quota', $e->errors());
            }
        }
        Auth::user()->company_id = 2;
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $controller->updateQuota(Request::create('/', 'POST', ['id' => 1, 'intermediate_quota' => 20]));
    }

    public function test_restoring_overissued_paid_ticket_cannot_bypass_quota()
    {
        DB::table('schedules')->update(['intermediate_quota' => 0]);
        $old = $this->ticket(['type' => 'over-issue', 'deleted_at' => now()]);
        $response = app(BookingController::class)->revertOverIssueSeat(Request::create('/', 'POST', ['ticket_id' => $old->id]));
        $this->assertSame(422, $response->status());
        $this->assertArrayHasKey('quota', $response->getData(true)['errors']);
        $this->assertSame('over-issue', Ticket::withTrashed()->find($old->id)->type);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_mysql_lock_serializes_bookings_and_count_uses_current_read()
    {
        if (!$this->database) { $this->markTestSkipped('Run with RUN_SCHEDULE_QUOTA_MYSQL_TESTS=1 for MySQL locking verification.'); }
        $other = DB::connection('quota_second');
        $other->statement('SET SESSION innodb_lock_wait_timeout = 1');
        DB::beginTransaction();
        $this->service->assertAvailable(ScheduleDetail::find(2), ['1', '2']);
        try {
            $other->transaction(function () use ($other) { $other->table('schedules')->where('id', 1)->lockForUpdate()->first(); });
            $this->fail('Concurrent connection should not acquire the quota lock.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertSame(1205, (int) $e->errorInfo[1]);
        }
        DB::commit();
        DB::beginTransaction();
        $this->assertSame(0, Ticket::count()); // Establish an old REPEATABLE READ snapshot.
        foreach ([1, 2] as $seat) {
            $other->table('tickets')->insert(['company_id' => 1, 'schedule_id' => 1, 'schedule_date' => '2026-10-20',
                'departure_city_id' => 10, 'destination_city_id' => 20, 'type' => 'booked', 'seat_no' => $seat]);
        }
        $this->rejects(2, 1); // Must see the committed tickets, not the old snapshot.
        DB::rollBack();
    }
}
