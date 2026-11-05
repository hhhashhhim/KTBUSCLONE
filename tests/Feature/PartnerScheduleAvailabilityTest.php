<?php

namespace Tests\Feature;

use App\Support\CallCenterSeatAvailability;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerScheduleAvailabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'partner_availability_test',
            'database.connections.partner_availability_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('partner_availability_test');
        Schema::clearResolvedInstance('db.schema');
        Schema::create('routes_fares', function (Blueprint $table) {
            $table->id(); $table->integer('company_id'); $table->integer('route_id');
            $table->integer('departure_city_id'); $table->integer('destination_city_id'); $table->softDeletes();
        });
        Schema::create('tickets', function (Blueprint $table) {
            $table->id(); $table->integer('company_id'); $table->integer('schedule_id');
            $table->date('schedule_date'); $table->string('seat_no');
            $table->integer('departure_city_id'); $table->integer('destination_city_id'); $table->softDeletes();
        });
        DB::table('routes_fares')->insert([
            ['company_id' => 1, 'route_id' => 1, 'departure_city_id' => 1, 'destination_city_id' => 2],
            ['company_id' => 1, 'route_id' => 1, 'departure_city_id' => 2, 'destination_city_id' => 3],
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('partner_availability_test');
        parent::tearDown();
    }

    private function detail(?string $choices = null): object
    {
        $map = [array_map(function ($number) {
            return ['seatNo' => (string) $number, 'reserved' => true, 'type' => 0];
        }, range(1, 6))];
        return (object) ['id' => 10, 'schedule_id' => 1, 'schedule_date' => '2026-11-06',
            'departure_id' => 2, 'destination_id' => 3,
            'bus_class_map' => (object) ['seat_map' => $map],
            'schedule' => (object) ['route_id' => 1, 'route' => (object) ['online_seat_choices' => $choices]]];
    }

    public function test_midway_sales_do_not_make_onward_journey_appear_sold_out(): void
    {
        for ($seat = 1; $seat <= 5; $seat++) {
            DB::table('tickets')->insert(['company_id' => 1, 'schedule_id' => 1,
                'schedule_date' => '2026-11-06', 'seat_no' => (string) $seat,
                'departure_city_id' => 1, 'destination_city_id' => 2]);
        }
        DB::table('tickets')->insert(['company_id' => 1, 'schedule_id' => 1,
            'schedule_date' => '2026-11-06', 'seat_no' => '6',
            'departure_city_id' => 2, 'destination_city_id' => 3]);
        $service = new CallCenterSeatAvailability;
        $user = (object) ['company_id' => 1, 'check_allowed_seats' => 0];
        $terminal = (object) ['available_seats' => null];

        // Six tickets exist, but five seats are free for Multan -> Karachi.
        $this->assertSame(5, $service->forPartnerSchedules(collect([$this->detail()]), $user, $terminal)[10]);
        $terminal->available_seats = '2-3';
        $this->assertSame(2, $service->forPartnerSchedules(collect([$this->detail()]), $user, $terminal)[10]);
        $this->assertSame(1, $service->forPartnerSchedules(collect([$this->detail('3,4')]), $user, $terminal)[10]);
        DB::table('tickets')->insert(['company_id' => 1, 'schedule_id' => 1,
            'schedule_date' => '2026-11-06', 'seat_no' => '3',
            'departure_city_id' => 1, 'destination_city_id' => 3]);
        $this->assertSame(0, $service->forPartnerSchedules(collect([$this->detail('3,4')]), $user, $terminal)[10]);
    }
}
