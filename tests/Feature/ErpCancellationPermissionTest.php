<?php

namespace Tests\Feature;

use App\Models\admin\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ErpCancellationPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.erp_cancellation_testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]);
        config()->set('database.default', 'erp_cancellation_testing');
        config()->set('cache.default', 'array');
        // Register the real ERP endpoints for each fresh application instance.
        Route::prefix('api')->middleware('api')->group(base_path('routes/api/booking.php'));
        $tables = [
            'roles' => ['', 'permissions'],
            'tickets' => ['customer_id schedule_id departure_city_id destination_city_id seat_fare points_usage refund_percentage refund_amount',
                'date schedule_date schedule_time seat_no type refund_reason'],
            'customers' => ['', 'cnic'],
            'card_categories' => ['point_flat', 'point_type'],
            'card_assigns' => ['card_category_id starting_points', 'cnic'],
            'ticket_e_l_t_s' => ['ticket_id', ''],
            'booking_cancels' => ['ticket_id percentage added_by', 'reason type'],
            'activity_logs' => ['activity_by', 'message requested_host'],
        ];
        foreach ($tables as $name => [$integers, $strings]) {
            Schema::create($name, function (Blueprint $table) use ($integers, $strings) {
                $table->id();
                $table->integer('company_id')->nullable();
                foreach (array_filter(explode(' ', $integers)) as $column) {
                    $table->integer($column)->nullable();
                }
                foreach (array_filter(explode(' ', $strings)) as $column) {
                    $table->text($column)->nullable();
                }
                $table->timestamps();
                $table->softDeletes();
            });
        }
        DB::table('customers')->insert(['id' => 1, 'company_id' => 1, 'cnic' => 'test-customer']);
        DB::table('card_categories')->insert(['id' => 1, 'point_type' => 'flatPoints', 'point_flat' => 100]);
        DB::table('card_assigns')->insert([
            'id' => 1, 'company_id' => 1, 'cnic' => 'test-customer', 'card_category_id' => 1, 'starting_points' => 1000,
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('erp_cancellation_testing');
        parent::tearDown();
    }

    /** @dataProvider permissionCases */
    public function test_ticket_status_requires_its_matching_permission(string $type, array $permissions, bool $bulk, bool $allowed)
    {
        $ticket = $this->ticket($type);
        $this->operator($permissions);
        $before = $this->snapshot();
        $response = $this->cancel($ticket, $bulk);
        if (!$allowed) {
            $response->assertForbidden();
            $this->assertSame($before, $this->snapshot());
        } else {
            $response->assertOk()->assertExactJson(['tickets' => $type === 'booked' ? [$ticket->id] : []]);
            $this->assertCancelled($ticket, $type);
            $this->assertDatabaseCount('activity_logs', 1);
            $this->assertDatabaseHas('card_assigns', ['id' => 1, 'starting_points' => $type === 'booked' ? 992 : 1002]);
        }
        $this->assertSame(0, DB::transactionLevel());
    }

    public static function permissionCases(): array
    {
        $cases = [];
        foreach (['booked' => 'cancel-ticket', 'advance booking' => 'reserved-cancel'] as $type => $required) {
            foreach ([[], ['reserved-cancel'], ['cancel-ticket'], ['reserved-cancel', 'cancel-ticket']] as $permissions) {
                foreach ([false, true] as $bulk) {
                    $cases[$type . ' / ' . implode(',', $permissions) . ' / ' . ($bulk ? 'bulk' : 'single')] =
                        [$type, $permissions, $bulk, in_array($required, $permissions, true)];
                }
            }
        }
        foreach ([false, true] as $bulk) {
            $cases['unsupported status / ' . ($bulk ? 'bulk' : 'single')] =
                ['pending booking', ['reserved-cancel', 'cancel-ticket'], $bulk, false];
        }
        return $cases;
    }

    /** @dataProvider mixedCases */
    public function test_mixed_bulk_selection_is_authorized_before_any_ticket_changes(array $permissions, bool $issuedFirst)
    {
        $first = $this->ticket($issuedFirst ? 'booked' : 'advance booking');
        $second = $this->ticket($issuedFirst ? 'advance booking' : 'booked');
        $this->operator($permissions);
        $before = $this->snapshot();
        $response = $this->postJson('/api/web/v1/booking/canceling/all', [
            'cancelAllSeat' => [$first->id, $second->id], 'percentage' => 50, 'reason' => 'Staff cancellation',
        ]);
        if (count($permissions) < 2) {
            $response->assertForbidden();
            $this->assertSame($before, $this->snapshot());
        } else {
            $response->assertOk()->assertExactJson(['tickets' => [$issuedFirst ? $first->id : $second->id]]);
            $this->assertCancelled($first, $first->type);
            $this->assertCancelled($second, $second->type);
            $this->assertDatabaseCount('booking_cancels', 2);
            $this->assertDatabaseCount('activity_logs', 1);
        }
        $this->assertSame(0, DB::transactionLevel());
    }

    public static function mixedCases(): array
    {
        $cases = [];
        foreach ([['reserved-cancel'], ['cancel-ticket'], ['reserved-cancel', 'cancel-ticket']] as $permissions) {
            foreach ([false, true] as $issuedFirst) {
                $cases[] = [$permissions, $issuedFirst];
            }
        }
        return $cases;
    }

    /** @dataProvider unavailableCases */
    public function test_unavailable_ticket_rejects_the_whole_request(string $unavailable, bool $bulk)
    {
        $valid = $this->ticket('advance booking');
        $target = $this->ticket('advance booking');
        if ($unavailable === 'foreign company') {
            $target->update(['company_id' => 2]);
        } elseif ($unavailable === 'deleted') {
            $target->delete();
        } else {
            DB::table('tickets')->where('id', $target->id)->delete();
        }
        $this->operator(['reserved-cancel', 'cancel-ticket']);
        $before = $this->snapshot();
        if ($bulk) {
            $this->postJson('/api/web/v1/booking/canceling/all', [
                'cancelAllSeat' => [$valid->id, $target->id], 'percentage' => 50, 'reason' => 'Staff cancellation',
            ])->assertNotFound();
        } else {
            $this->cancel($target, false)->assertNotFound();
        }
        $this->assertSame($before, $this->snapshot());
        $this->assertSame(0, DB::transactionLevel());
    }

    public static function unavailableCases(): array
    {
        return [['foreign company', false], ['foreign company', true], ['deleted', false], ['deleted', true],
            ['missing', false], ['missing', true]];
    }

    /** @dataProvider invalidSelections */
    public function test_invalid_bulk_selection_has_no_side_effects($selection)
    {
        $this->ticket('advance booking');
        $this->operator(['reserved-cancel', 'cancel-ticket']);
        $before = $this->snapshot();
        $this->postJson('/api/web/v1/booking/canceling/all', ['cancelAllSeat' => $selection])
            ->assertStatus(422);
        $this->assertSame($before, $this->snapshot());
        $this->assertSame(0, DB::transactionLevel());
    }

    public static function invalidSelections(): array
    {
        return [[null], [[]], ['1'], [[1, 1]], [[1, 'bad-id']], [[1, 0]]];
    }

    private function ticket(string $type): Ticket
    {
        $ticket = Ticket::create([
            'company_id' => 1, 'customer_id' => 1, 'schedule_id' => 10,
            'departure_city_id' => 1, 'destination_city_id' => 2, 'seat_fare' => 1000, 'points_usage' => 2,
            'date' => '2026-09-22', 'schedule_date' => '2026-09-22', 'schedule_time' => '12:00:00',
            'seat_no' => (string) (Ticket::withTrashed()->count() + 1), 'type' => $type,
        ]);
        DB::table('ticket_e_l_t_s')->insert(['ticket_id' => $ticket->id]);
        return $ticket;
    }

    private function operator(array $permissions): void
    {
        $buttons = array_map(function ($name) { return ['name' => $name, 'allow' => true]; }, $permissions);
        $role = Role::create(['permissions' => [['childs' => [['buttons' => $buttons]]]]]);
        Sanctum::actingAs(new User(['id' => 20, 'company_id' => 1, 'role_id' => $role->id, 'name' => 'ERP operator']));
    }

    private function cancel(Ticket $ticket, bool $bulk)
    {
        return $this->postJson('/api/web/v1/booking/canceling' . ($bulk ? '/all' : ''), $bulk ? [
            'cancelAllSeat' => [$ticket->id], 'percentage' => 50, 'reason' => 'Staff cancellation',
        ] : [
            'date' => $ticket->date, 'schedule_id' => $ticket->schedule_id, 'customer_id' => $ticket->customer_id,
            'departure_id' => $ticket->departure_city_id, 'destination_id' => $ticket->destination_city_id,
            'seat_no' => $ticket->seat_no, 'percentage' => 50, 'remarks' => 'Staff cancellation',
        ]);
    }

    private function assertCancelled(Ticket $ticket, string $previousType): void
    {
        $this->assertSoftDeleted('tickets', ['id' => $ticket->id, 'type' => 'canceled']);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'refund_percentage' => 50, 'refund_amount' => 500]);
        $this->assertDatabaseHas('booking_cancels', [
            'ticket_id' => $ticket->id, 'type' => $previousType, 'added_by' => 20, 'reason' => 'Staff cancellation',
        ]);
        $this->assertSoftDeleted('ticket_e_l_t_s', ['ticket_id' => $ticket->id]);
    }

    private function snapshot(): array
    {
        $rows = [];
        foreach (['tickets', 'card_assigns', 'ticket_e_l_t_s', 'booking_cancels', 'activity_logs'] as $table) {
            $rows[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }
        return $rows;
    }
}
