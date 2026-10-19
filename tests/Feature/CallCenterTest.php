<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CallCenter\CallLog;
use App\Support\CallCenterPermissions;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CallCenterTest extends TestCase
{
    private const BASE = '/api/web/v1/call-center';

    protected function setUp(): void
    {
        parent::setUp();
        // Explicitly isolate this suite: never run legacy migrations or touch a configured database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        Carbon::setTestNow(Carbon::parse('2026-10-13 09:00:00', 'Asia/Karachi'));
        $this->legacySchema();
        require_once database_path('migrations/2026_10_13_000001_create_call_center_tables.php');
        (new \CreateCallCenterTables)->up();
        require_once database_path('migrations/2026_10_15_000001_add_call_center_passenger_lookup_index.php');
        (new \AddCallCenterPassengerLookupIndex)->up();
        require_once database_path('migrations/2026_10_15_000002_add_call_center_inquiry_topic.php');
        (new \AddCallCenterInquiryTopic)->up();
        require_once database_path('migrations/2026_10_15_000003_add_call_center_complaint_journey.php');
        (new \AddCallCenterComplaintJourney)->up();
        require_once database_path('migrations/2026_10_15_000004_add_call_center_complaint_resolution.php');
        (new \AddCallCenterComplaintResolution)->up();
        require_once database_path('migrations/2026_10_16_000001_add_call_center_customer_agreement.php');
        (new \AddCallCenterCustomerAgreement)->up();
        require_once database_path('migrations/2026_10_16_000003_create_call_center_notifications_table.php');
        (new \CreateCallCenterNotificationsTable)->up();
        require_once database_path('migrations/2026_10_16_000004_add_call_center_parent_lookup_index.php');
        (new \AddCallCenterParentLookupIndex)->up();
        DB::table('companies')->insert([
            ['id' => 1, 'modules' => json_encode(CallCenterPermissions::append([], true))], ['id' => 2, 'modules' => json_encode(CallCenterPermissions::append([], true))],
        ]);
        DB::table('terminals')->insert([
            ['id' => 10, 'company_id' => 1, 'name' => 'Lahore', 'hide' => 0],
            ['id' => 11, 'company_id' => 1, 'name' => 'Karachi', 'hide' => 0],
            ['id' => 20, 'company_id' => 2, 'name' => 'Other company', 'hide' => 0],
        ]);
        DB::table('cities')->insert([['id' => 1, 'name' => 'Lahore'], ['id' => 2, 'name' => 'Karachi']]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function legacySchema(): void
    {
        Schema::create('companies', function (Blueprint $t) { $t->id(); $t->text('modules')->nullable(); $t->string('name')->nullable(); $t->softDeletes(); });
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->string('name')->nullable(); $t->text('permissions')->nullable(); $t->softDeletes(); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->integer('company_id'); $t->integer('terminal_id')->nullable(); $t->integer('role_id'); $t->string('name');
            $t->integer('hide')->default(0); $t->text('route_ids')->nullable(); $t->integer('is_super_admin')->default(0); $t->timestamps();
        });
        Schema::create('terminals', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->string('name'); $t->integer('hide')->default(0); $t->softDeletes(); });
        Schema::create('cities', function (Blueprint $t) { $t->id(); $t->string('name'); $t->softDeletes(); });
        Schema::create('customers', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->string('name'); $t->string('contact'); $t->string('cnic'); $t->softDeletes(); });
        Schema::create('tickets', function (Blueprint $t) {
            $t->id(); $t->integer('company_id'); $t->integer('terminal_id'); $t->integer('route_id')->default(1); $t->integer('customer_id');
            $t->integer('booking_no')->nullable(); $t->integer('invoice_id')->nullable(); $t->integer('seat_no')->default(1); $t->string('type')->default('booked');
            $t->date('schedule_date')->nullable(); $t->string('schedule_time')->nullable(); $t->decimal('seat_fare')->default(2000);
            $t->integer('departure_city_id')->default(1); $t->integer('destination_city_id')->default(2); $t->boolean('is_partial')->default(false);
            $t->decimal('refund_amount')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('ticket_reschedules', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->integer('old_ticket_id'); $t->integer('new_ticket_id')->nullable(); $t->integer('added_by'); $t->softDeletes(); $t->timestamps(); });
        Schema::create('booking_cancels', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->integer('ticket_id'); $t->integer('added_by'); $t->string('type'); $t->softDeletes(); $t->timestamps(); });
    }

    private function agent(int $id = 1, int $company = 1, int $terminal = 10, bool $supervise = false, bool $all = false, bool $allow = true, string $routes = 'all'): User
    {
        $permissions = [['name' => 'ticketing', 'allow' => true, 'childs' => [['name' => 'call-center', 'allow' => $allow, 'buttons' => [
            ['name' => 'call-center-supervise', 'allow' => $supervise], ['name' => 'call-center-all-terminals', 'allow' => $all],
        ]]]]];
        $permissions = CallCenterPermissions::append($permissions, false, true);
        DB::table('roles')->insert(['id' => $id, 'company_id' => $company, 'permissions' => json_encode($permissions)]);
        return User::create(['id' => $id, 'company_id' => $company, 'terminal_id' => $terminal, 'role_id' => $id, 'name' => 'Agent ' . $id, 'route_ids' => $routes]);
    }

    private function ticket(int $id = 100, array $attrs = []): void
    {
        $customer = $attrs['customer_id'] ?? $id;
        if (!DB::table('customers')->where('id', $customer)->exists()) {
            DB::table('customers')->insert(['id' => $customer, 'company_id' => $attrs['company_id'] ?? 1, 'name' => 'Passenger ' . $customer,
                'contact' => '03001234567', 'cnic' => '3520212345671']);
        }
        DB::table('tickets')->insert(array_merge(['id' => $id, 'company_id' => 1, 'terminal_id' => 10, 'customer_id' => $customer,
            'invoice_id' => 8000 + $id, 'booking_no' => $id, 'schedule_date' => '2026-10-14', 'schedule_time' => '10:30'], $attrs));
    }

    private function logCall(User $agent, array $attrs = []): CallLog
    {
        return CallLog::create(array_merge(['company_id' => $agent->company_id, 'terminal_id' => $agent->terminal_id, 'agent_id' => $agent->id,
            'call_date' => '2026-10-13', 'called_at' => '2026-10-13 09:00:00', 'shift' => 1, 'category' => 'GI', 'status' => 'new'], $attrs));
    }

    private function snapshot(int $shift = 0): string
    {
        return $this->getJson(self::BASE . ($shift ? '/summary' : '/daily-report') . '?date=2026-10-13')->assertOk()
            ->json($shift ? 'data.snapshot_tokens.' . $shift : 'data.snapshot_token');
    }

    public function test_complaint_workspace_handover_preserves_history_and_notifies_only_new_person(): void
    {
        $owner = $this->agent(); $first = $this->agent(2); $second = $this->agent(3);
        $call = $this->logCall($owner, ['category' => 'C', 'assigned_to' => 2, 'remarks' => 'Original complaint', 'status' => 'waiting', 'callback_at' => '2026-10-13 12:00:00']);
        $followup = $this->logCall($owner, ['category' => 'FU', 'parent_call_id' => $call->id, 'remarks' => 'Customer called again.']);
        DB::table('call_center_audits')->insert(['call_id' => $call->id, 'actor_id' => 1, 'action' => 'complaint_update', 'changes' => json_encode(['remarks' => 'Checking with terminal.']), 'created_at' => '2026-10-13 08:30:00']);
        $this->grant($first, ['view-complaints', 'edit-calls', 'assign-calls']); Sanctum::actingAs($first);
        $url = self::BASE . '/calls/' . $call->id . '/assign-complaint';
        $this->postJson($url, ['assigned_to' => 3, 'expected_assigned_to' => 2, 'remarks' => 'Must be ignored', 'status' => 'closed'])
            ->assertOk()->assertJsonPath('data.can_view', false)->assertJsonPath('data.assignee.name', 'Agent 3')->assertJsonPath('data.status', 'waiting')
            ->assertJsonPath('data.remarks', 'Original complaint')->assertJsonPath('data.callback_at', '2026-10-13 12:00:00')->assertJsonPath('data.complaint_history_count', 3);
        $this->getJson(self::BASE . '/calls/' . $call->id)->assertNotFound();
        Sanctum::actingAs($second);
        $this->getJson(self::BASE . '/notifications')->assertJsonPath('data.unread_count', 1);
        $history = $this->getJson(self::BASE . '/calls/' . $call->id . '/complaint-updates')->assertOk()->assertJsonPath('data.total', 3)->json('data.data');
        $assignment = collect($history)->firstWhere('kind', 'assignment');
        $this->assertSame(2, $assignment['from_assignee']['id']); $this->assertSame(3, $assignment['to_assignee']['id']); $this->assertSame(2, $assignment['actor']['id']);
        $this->assertSame(1, collect($history)->where('kind', 'follow_up')->count());
        Sanctum::actingAs($owner);
        $this->postJson($url, ['assigned_to' => 3, 'expected_assigned_to' => 2])->assertOk(); // retry
        $this->assertSame(1, DB::table('call_center_audits')->where('action', 'updated')->count());
        $this->assertSame(1, DB::table('call_center_notifications')->count());
        $this->patchJson(self::BASE . '/calls/' . $call->id, ['assigned_to' => 2])->assertOk(); // existing call-log flow
        $this->getJson(self::BASE . '/calls/' . $call->id . '/complaint-updates')->assertJsonPath('data.total', 4)->assertJsonPath('data.data.0.to_assignee.id', 2);
        Sanctum::actingAs($second); $this->getJson(self::BASE . '/notifications')->assertJsonPath('data.unread_count', 0);
        Sanctum::actingAs($first); $this->getJson(self::BASE . '/notifications')->assertJsonPath('data.unread_count', 1);
        $this->assertSame('Original complaint', $call->fresh()->remarks);
        $this->assertDatabaseCount('call_center_calls', 2);
    }

    public function test_complaint_assignment_rejects_stale_closed_forbidden_and_out_of_scope_changes(): void
    {
        $owner = $this->agent(); $other = $this->agent(2); $foreign = $this->agent(3, 2, 20);
        $call = $this->logCall($owner, ['category' => 'C', 'assigned_to' => 1]);
        $url = self::BASE . '/calls/' . $call->id . '/assign-complaint';
        Sanctum::actingAs($owner);
        foreach ([['view-complaints', 'edit-calls'], ['view-complaints', 'assign-calls'], ['edit-calls', 'assign-calls']] as $grants) {
            $this->grant($owner, $grants);
            $this->postJson($url, ['assigned_to' => 2, 'expected_assigned_to' => 1])->assertForbidden();
            $this->getJson(self::BASE . '/calls/' . $call->id . '/complaint-assignees')->assertForbidden();
        }
        $this->grant($owner, ['view-complaints', 'edit-calls', 'assign-calls']);
        $this->postJson($url, ['assigned_to' => 2])->assertUnprocessable();
        $this->postJson($url, ['assigned_to' => null, 'expected_assigned_to' => 1])->assertUnprocessable();
        $this->postJson($url, ['assigned_to' => 2, 'expected_assigned_to' => null])->assertStatus(409);
        $call->update(['status' => 'closed']);
        $this->postJson($url, ['assigned_to' => 2, 'expected_assigned_to' => 1])->assertStatus(409);
        $call->update(['status' => 'new']);
        foreach ([$this->logCall($other, ['category' => 'C']), $this->logCall($foreign, ['category' => 'C']), $this->logCall($owner, ['category' => 'C', 'terminal_id' => 11])] as $hidden) {
            $this->postJson(self::BASE . '/calls/' . $hidden->id . '/assign-complaint', ['assigned_to' => 2, 'expected_assigned_to' => null])->assertNotFound();
            $this->getJson(self::BASE . '/calls/' . $hidden->id . '/complaint-assignees')->assertNotFound();
        }
        $general = $this->logCall($owner);
        $this->postJson(self::BASE . '/calls/' . $general->id . '/assign-complaint', ['assigned_to' => 2, 'expected_assigned_to' => null])->assertUnprocessable();
        $this->assertSame(1, $call->fresh()->assigned_to);
        $this->assertDatabaseCount('call_center_audits', 0); $this->assertDatabaseCount('call_center_notifications', 0);
    }

    public function test_complaint_assignee_choices_and_save_enforce_recipient_access(): void
    {
        $owner = $this->agent(); $eligible = $this->agent(2); $wrongTerminal = $this->agent(3, 1, 11);
        $foreign = $this->agent(4, 2, 20); $wrongRoute = $this->agent(5, 1, 10, false, false, true, '[2]');
        $hidden = $this->agent(6); $hidden->update(['hide' => 1]); $noView = $this->agent(7); $this->grant($noView, ['view-calls']);
        $crossTerminal = $this->agent(8, 1, 11, false, true);
        $this->ticket(); $call = $this->logCall($owner, ['category' => 'C', 'ticket_id' => 100]); Sanctum::actingAs($owner);
        $choices = $this->getJson(self::BASE . '/calls/' . $call->id . '/complaint-assignees')->assertOk()->json('data');
        $this->assertSame([1, 2, 8], array_column($choices, 'id'));
        foreach ([3, 4, 5, 6, 7, 999] as $id) {
            $this->postJson(self::BASE . '/calls/' . $call->id . '/assign-complaint', ['assigned_to' => $id, 'expected_assigned_to' => null])->assertUnprocessable()->assertJsonValidationErrors('assigned_to');
        }
        $this->postJson(self::BASE . '/calls/' . $call->id . '/assign-complaint', ['assigned_to' => 8, 'expected_assigned_to' => null])->assertOk()->assertJsonPath('data.can_view', true);
        $this->grant($owner, ['view-complaints']);
        $this->getJson(self::BASE . '/calls/' . $call->id . '/complaint-updates')->assertOk()->assertJsonPath('data.data.0.from_assignee', null)->assertJsonPath('data.data.0.to_assignee.name', 'Agent 8');
        DB::table('users')->where('id', 8)->delete();
        $this->getJson(self::BASE . '/calls/' . $call->id . '/complaint-updates')->assertOk()->assertJsonPath('data.data.0.to_assignee.name', 'Former user #8');
    }

    public function test_complaint_assignment_notifications_are_persistent_private_and_read_idempotently(): void
    {
        $owner = $this->agent(); $recipient = $this->agent(2); $outsider = $this->agent(3);
        Sanctum::actingAs($owner);
        $callId = $this->postJson(self::BASE . '/calls', ['category' => 'C', 'shift' => 1, 'remarks' => 'Luggage missing', 'assigned_to' => 2])->assertCreated()->json('data.id');
        $this->getJson(self::BASE . '/notifications')->assertOk()->assertJsonPath('data.unread_count', 0);
        $this->patchJson(self::BASE . '/calls/' . $callId, ['remarks' => 'Luggage still missing'])->assertOk();
        $this->assertSame(1, DB::table('call_center_notifications')->count());
        Sanctum::actingAs($recipient);
        $list = $this->getJson(self::BASE . '/notifications')->assertOk()->assertJsonPath('data.unread_count', 1)
            ->assertJsonPath('data.notifications.data.0.message', 'Complaint CC-' . $callId . ' assigned to you by Agent 1.')
            ->assertJsonPath('data.notifications.data.0.call_id', $callId);
        $id = $list->json('data.notifications.data.0.id');
        $this->getJson(self::BASE . '/calls/' . $callId)->assertOk();
        Sanctum::actingAs($outsider);
        $this->postJson(self::BASE . '/notifications/' . $id . '/read')->assertNotFound();
        $this->getJson(self::BASE . '/notifications')->assertJsonPath('data.unread_count', 0);
        Sanctum::actingAs($recipient);
        $this->postJson(self::BASE . '/notifications/' . $id . '/read')->assertOk();
        $readAt = DB::table('call_center_notifications')->where('id', $id)->value('read_at');
        Carbon::setTestNow(Carbon::now('Asia/Karachi')->addMinute());
        $this->postJson(self::BASE . '/notifications/' . $id . '/read')->assertOk();
        $this->assertSame($readAt, DB::table('call_center_notifications')->where('id', $id)->value('read_at'));
        $this->getJson(self::BASE . '/notifications')->assertJsonPath('data.unread_count', 0)->assertJsonCount(1, 'data.notifications.data');
    }

    public function test_reassignment_retires_old_notifications_and_does_not_notify_for_self_or_other_categories(): void
    {
        $owner = $this->agent(); $first = $this->agent(2); $second = $this->agent(3);
        Sanctum::actingAs($owner);
        $id = $this->postJson(self::BASE . '/calls', ['category' => 'C', 'shift' => 1, 'assigned_to' => 2])->assertCreated()->json('data.id');
        $oldNotification = DB::table('call_center_notifications')->value('id');
        $this->patchJson(self::BASE . '/calls/' . $id, ['assigned_to' => 3])->assertOk();
        Sanctum::actingAs($first);
        $this->getJson(self::BASE . '/notifications')->assertJsonPath('data.unread_count', 0);
        $this->postJson(self::BASE . '/notifications/' . $oldNotification . '/read')->assertNotFound();
        Sanctum::actingAs($second);
        $this->getJson(self::BASE . '/notifications')->assertJsonPath('data.unread_count', 1);
        Sanctum::actingAs($owner);
        $this->patchJson(self::BASE . '/calls/' . $id, ['assigned_to' => 2])->assertOk();
        Sanctum::actingAs($first);
        $this->getJson(self::BASE . '/notifications')->assertJsonPath('data.unread_count', 1)->assertJsonCount(1, 'data.notifications.data');
        Sanctum::actingAs($owner);
        $this->patchJson(self::BASE . '/calls/' . $id, ['assigned_to' => 1])->assertOk();
        $this->postJson(self::BASE . '/calls', ['category' => 'GI', 'inquiry_topic' => 'other', 'shift' => 1, 'assigned_to' => 2])->assertCreated();
        $this->postJson(self::BASE . '/calls', ['category' => 'C', 'shift' => 1, 'assigned_to' => 1])->assertCreated();
        $this->assertSame(0, DB::table('call_center_notifications')->whereNull('superseded_at')->count());
    }

    public function test_notifications_recheck_company_terminal_route_and_role_access(): void
    {
        $owner = $this->agent(); $recipient = $this->agent(2); $foreign = $this->agent(3, 2, 20);
        $this->ticket(); Sanctum::actingAs($owner);
        $callId = $this->postJson(self::BASE . '/calls', ['category' => 'C', 'shift' => 1, 'ticket_id' => 100, 'assigned_to' => 2])->assertCreated()->json('data.id');
        $id = DB::table('call_center_notifications')->value('id');
        Sanctum::actingAs($foreign);
        $this->getJson(self::BASE . '/notifications')->assertJsonPath('data.unread_count', 0);
        $this->postJson(self::BASE . '/notifications/' . $id . '/read')->assertNotFound();
        Sanctum::actingAs($recipient);
        $recipient->route_ids = '[2]'; $recipient->save();
        $this->getJson(self::BASE . '/notifications')->assertJsonPath('data.unread_count', 0);
        $this->postJson(self::BASE . '/notifications/' . $id . '/read')->assertNotFound();
        $recipient->route_ids = 'all'; $recipient->terminal_id = 11; $recipient->save();
        $this->getJson(self::BASE . '/notifications')->assertJsonPath('data.unread_count', 0);
        $recipient->terminal_id = 10; $recipient->save();
        $this->getJson(self::BASE . '/notifications')->assertJsonPath('data.unread_count', 1);
        $this->grant($recipient, ['view-calls']);
        $this->getJson(self::BASE . '/notifications')->assertForbidden();
        $this->postJson(self::BASE . '/notifications/' . $id . '/read')->assertForbidden();
        Sanctum::actingAs($owner);
        $this->postJson(self::BASE . '/calls', ['category' => 'C', 'shift' => 1, 'assigned_to' => 2])->assertUnprocessable()->assertJsonValidationErrors('assigned_to');
        $this->assertSame(1, DB::table('call_center_notifications')->count());
    }

    public function test_notification_pagination_counts_all_unread_and_closed_complaint_links_still_work(): void
    {
        $owner = $this->agent(); $recipient = $this->agent(2); Sanctum::actingAs($owner);
        for ($i = 0; $i < 22; $i++) {
            $id = $this->postJson(self::BASE . '/calls', ['category' => 'C', 'shift' => 1, 'assigned_to' => 2])->assertCreated()->json('data.id');
        }
        $this->postJson(self::BASE . '/calls/' . $id . '/close-complaint', ['resolution_remarks' => 'Resolved'])->assertOk();
        Sanctum::actingAs($recipient);
        $this->getJson(self::BASE . '/notifications')->assertJsonPath('data.unread_count', 22)->assertJsonCount(20, 'data.notifications.data');
        $this->getJson(self::BASE . '/notifications?page=2')->assertJsonCount(2, 'data.notifications.data');
        $this->getJson(self::BASE . '/notifications?page=0')->assertUnprocessable();
        $this->getJson(self::BASE . '/calls/' . $id)->assertOk()->assertJsonPath('data.status', 'closed');
    }

    public function test_access_requires_authentication_and_enabled_company_role_parent(): void
    {
        $this->getJson(self::BASE . '/options')->assertUnauthorized();
        $user = $this->agent(1, 1, 10, false, false, false);
        Sanctum::actingAs($user);
        $this->getJson(self::BASE . '/options')->assertForbidden();
        DB::table('roles')->where('id', 1)->update(['permissions' => json_encode([['name' => 'ticketing', 'allow' => false, 'childs' => [['name' => 'call-center', 'allow' => true]]]])]);
        $user->unsetRelation('role');
        $this->getJson(self::BASE . '/options')->assertForbidden();
    }

    public function test_lookup_masks_cnic_and_enforces_company_terminal_and_route_boundaries(): void
    {
        $user = $this->agent(1, 1, 10, false, false, true, '[1]'); Sanctum::actingAs($user);
        $this->ticket(100); $this->ticket(101, ['terminal_id' => 11]);
        $this->ticket(102, ['route_id' => 2]); $this->ticket(103, ['company_id' => 2, 'terminal_id' => 20]);
        $result = $this->getJson(self::BASE . '/passengers?search_by=phone&q=%2B92%20300-1234567')->assertOk();
        $result->assertJsonCount(1, 'data.passengers')->assertJsonPath('data.passengers.0.id', 100)->assertJsonMissing(['cnic' => '3520212345671']);
        $this->assertSame('*********5671', $result->json('data.passengers.0.cnic_masked'));
        $this->getJson(self::BASE . '/passengers?search_by=cnic&q=35202-1234567-1')->assertJsonCount(1, 'data.passengers');
        $this->getJson(self::BASE . '/passengers?search_by=phone&q=03119999999')->assertOk()->assertJsonCount(0, 'data.passengers');
        $this->getJson(self::BASE . '/passengers?search_by=cnic&q=3520299999999')->assertOk()->assertJsonCount(0, 'data.passengers');
        $this->getJson(self::BASE . '/passengers?search_by=phone&q=123')->assertUnprocessable();
        $this->getJson(self::BASE . '/passengers?search_by=ticket&q=100abc')->assertUnprocessable();
    }

    public function test_ticket_lookup_includes_the_matching_old_ticket(): void
    {
        Sanctum::actingAs($this->agent());
        $this->ticket(100);
        for ($i = 101; $i < 130; $i++) { $this->ticket($i, ['customer_id' => 100]); }
        $this->getJson(self::BASE . '/passengers?search_by=ticket&q=100')->assertOk()
            ->assertJsonPath('data.passengers.0.tickets.0.id', 100)->assertJsonCount(1, 'data.passengers.0.tickets');
    }

    public function test_partial_journey_is_not_reported_as_partial_payment_and_reschedule_is_not_cancelled(): void
    {
        Sanctum::actingAs($this->agent());
        $this->ticket(100, ['is_partial' => true]);
        $this->ticket(101, ['type' => 'reschedule', 'deleted_at' => now()]);
        $this->getJson(self::BASE . '/passengers?search_by=ticket&q=100')->assertOk()
            ->assertJsonPath('data.passengers.0.tickets.0.payment_status', 'recorded_booking')
            ->assertJsonPath('data.passengers.0.tickets.0.can_link_booking', true);
        $this->getJson(self::BASE . '/passengers?search_by=ticket&q=101')->assertOk()
            ->assertJsonPath('data.passengers.0.tickets.0.payment_status', 'unconfirmed')
            ->assertJsonPath('data.passengers.0.tickets.0.can_link_booking', false);
    }

    public function test_general_information_and_overnight_shift_record_server_identity_and_audit(): void
    {
        Sanctum::actingAs($this->agent());
        $result = $this->postJson(self::BASE . '/calls', ['category' => 'GI', 'shift' => 3, 'call_date' => '2026-10-12', 'remarks' => 'Asked terminal location',
            'agent_id' => 999, 'company_id' => 2, 'terminal_id' => 20, 'called_at' => '2000-01-01'])->assertCreated();
        $result->assertJsonPath('data.agent_id', 1)->assertJsonPath('data.company_id', 1)->assertJsonPath('data.terminal_id', 10)
            ->assertJsonPath('data.call_date', '2026-10-12')->assertJsonPath('data.called_at', '2026-10-13 09:00:00')
            ->assertJsonPath('data.status', 'new')->assertJsonPath('data.seat_count', 0);
        $this->assertDatabaseCount('call_center_audits', 1);
        $this->postJson(self::BASE . '/calls', ['category' => 'GI', 'shift' => 1, 'call_date' => '2026-10-11'])->assertUnprocessable();
        $this->postJson(self::BASE . '/calls', ['category' => 'OTHER', 'shift' => 1])->assertUnprocessable();
        $this->postJson(self::BASE . '/calls', ['category' => ['GI'], 'shift' => 1])->assertUnprocessable();
    }

    public function test_booking_link_is_verified_and_seats_are_derived_without_mutating_tickets(): void
    {
        Sanctum::actingAs($this->agent()); $this->ticket(100); $this->ticket(101, ['invoice_id' => 8100, 'customer_id' => 100]);
        $this->ticket(102, ['type' => 'reserved']); $this->ticket(103, ['company_id' => 2, 'terminal_id' => 20]);
        $this->postJson(self::BASE . '/calls', ['category' => 'BK', 'shift' => 1])->assertCreated()->assertJsonPath('data.seat_count', 0);
        foreach ([102, 103] as $ticket) { $this->postJson(self::BASE . '/calls', ['category' => 'BK', 'shift' => 1, 'ticket_id' => $ticket])->assertUnprocessable(); }
        $this->postJson(self::BASE . '/calls', ['category' => 'BK', 'shift' => 1, 'ticket_id' => 100, 'customer_id' => 102])->assertUnprocessable();
        $call = $this->postJson(self::BASE . '/calls', ['category' => 'BK', 'shift' => 1, 'ticket_id' => 100, 'seat_count' => 99])->assertCreated()
            ->assertJsonPath('data.seat_count', 2)->assertJsonPath('data.customer_id', 100)->json('data.id');
        $this->assertDatabaseCount('tickets', 4); $this->assertDatabaseCount('booking_cancels', 0);
        DB::table('tickets')->where('id', 100)->update(['type' => 'canceled', 'deleted_at' => now()]);
        $this->patchJson(self::BASE . '/calls/' . $call, ['status' => 'closed', 'seat_count' => 20])->assertOk()->assertJsonPath('data.seat_count', 2);
    }

    public function test_followup_assignment_visibility_and_audit(): void
    {
        $user = $this->agent(); $other = $this->agent(2); $restricted = $this->agent(3, 1, 10, false, false, true, '[2]');
        $this->ticket(100); Sanctum::actingAs($user);
        $parent = $this->logCall($user, ['ticket_id' => 100, 'customer_id' => 100]);
        $this->postJson(self::BASE . '/calls', ['category' => 'FU', 'shift' => 1])->assertUnprocessable();
        $this->patchJson(self::BASE . '/calls/' . $parent->id, ['assigned_to' => $restricted->id])->assertUnprocessable();
        $this->patchJson(self::BASE . '/calls/' . $parent->id, ['assigned_to' => $other->id, 'callback_at' => '2026-10-13 12:00:00'])->assertOk();
        Sanctum::actingAs($other);
        $this->getJson(self::BASE . '/calls/' . $parent->id)->assertOk()->assertJsonPath('data.audit.0.actor.name', 'Agent 1');
        $this->postJson(self::BASE . '/calls', ['category' => 'FU', 'shift' => 1, 'parent_call_id' => $parent->id])->assertCreated();
        $this->getJson(self::BASE . '/summary?date=2026-10-13')->assertJsonPath('data.totals.total', 1)->assertJsonPath('data.totals.FU', 1);
    }

    public function test_calls_cannot_be_read_or_updated_across_agents_or_companies(): void
    {
        $user = $this->agent(); $other = $this->agent(2); $outsider = $this->agent(3, 2, 20);
        $otherCall = $this->logCall($other); $outsideCall = $this->logCall($outsider);
        Sanctum::actingAs($user);
        foreach ([$otherCall, $outsideCall] as $call) {
            $this->getJson(self::BASE . '/calls/' . $call->id)->assertNotFound();
            $this->patchJson(self::BASE . '/calls/' . $call->id, ['status' => 'closed'])->assertNotFound();
        }
        $this->getJson(self::BASE . '/calls?terminal_id=20')->assertForbidden();
        $this->postJson(self::BASE . '/daily-review', ['date' => '2026-10-13'])->assertForbidden();
    }

    public function test_summary_ratios_closure_counts_and_review_staleness(): void
    {
        $supervisor = $this->agent(1, 1, 10, true); $agent = $this->agent(2);
        $first = $this->logCall($agent);
        $this->logCall($agent, ['category' => 'BK', 'seat_count' => 3]);
        $this->logCall($agent, ['category' => 'C', 'status' => 'closed', 'shift' => 2]);
        $this->logCall($agent, ['category' => 'FU', 'shift' => 2]);
        Sanctum::actingAs($supervisor);
        $this->getJson(self::BASE . '/daily-report?date=2026-10-13')->assertOk()->assertJsonPath('data.totals.total', 4)
            ->assertJsonPath('data.totals.seats', 3)->assertJsonPath('data.ratios.booking', 25)->assertJsonPath('data.complaints.closed', 1)
            ->assertJsonPath('data.followups.pending', 1)->assertJsonCount(2, 'data.rows');
        $this->postJson(self::BASE . '/shift-review', ['date' => '2026-10-13', 'shift' => 1, 'remarks' => 'Checked', 'snapshot_token' => $this->snapshot(1)])->assertOk()->assertJsonPath('data.stale', false);
        $this->postJson(self::BASE . '/daily-review', ['date' => '2026-10-13', 'remarks' => 'Done', 'next_day_actions' => 'Call passenger', 'snapshot_token' => $this->snapshot()])->assertOk()->assertJsonPath('data.stale', false);
        $this->patchJson(self::BASE . '/calls/' . $first->id, ['remarks' => 'Changed'])->assertOk();
        $this->getJson(self::BASE . '/daily-report?date=2026-10-13')->assertJsonPath('data.review.stale', true);
        $this->getJson(self::BASE . '/summary?date=2026-10-13&shift=1')->assertJsonPath('data.reviews.0.stale', true);
        $this->getJson(self::BASE . '/daily-report?date=2026-10-12')->assertJsonPath('data.ratios.booking', 0);
    }

    public function test_cross_terminal_calls_are_attributed_to_recording_terminal_and_remain_visible(): void
    {
        $supervisor = $this->agent(1, 1, 10, true, true); Sanctum::actingAs($supervisor);
        $this->ticket(100, ['terminal_id' => 11]);
        $this->postJson(self::BASE . '/calls', ['category' => 'BK', 'shift' => 1, 'ticket_id' => 100])->assertCreated();
        $this->getJson(self::BASE . '/summary?date=2026-10-13&terminal_id=10')->assertOk()->assertJsonPath('data.totals.total', 1);
        $this->getJson(self::BASE . '/summary?date=2026-10-13&terminal_id=11')->assertJsonPath('data.totals.total', 0);
    }

    public function test_seat_movement_report_uses_recorded_events_and_excludes_unpaid_and_out_of_scope(): void
    {
        Sanctum::actingAs($this->agent(1, 1, 10, true));
        $this->ticket(100, ['type' => 'reschedule', 'deleted_at' => now()]);
        $this->ticket(101, ['type' => 'canceled', 'deleted_at' => now(), 'refund_amount' => 1000]);
        $this->ticket(102, ['type' => 'canceled', 'deleted_at' => now(), 'refund_amount' => 0]);
        $this->ticket(103, ['type' => 'canceled', 'deleted_at' => now(), 'refund_amount' => 1000, 'terminal_id' => 11]);
        $this->ticket(104, ['type' => 'canceled', 'deleted_at' => now(), 'refund_amount' => 1000]);
        DB::table('ticket_reschedules')->insert(['company_id' => 1, 'old_ticket_id' => 100, 'added_by' => 1, 'created_at' => now()]);
        foreach ([101, 101, 102, 103] as $id) {
            DB::table('booking_cancels')->insert(['company_id' => 1, 'ticket_id' => $id, 'added_by' => 1, 'type' => 'booked', 'created_at' => now()]);
        }
        DB::table('booking_cancels')->insert(['company_id' => 1, 'ticket_id' => 104, 'added_by' => 1, 'type' => 'reserved', 'created_at' => now()]);
        $this->getJson(self::BASE . '/daily-report?date=2026-10-13')->assertOk()->assertJsonCount(1, 'data.seat_movements')
            ->assertJsonPath('data.seat_movements.0.shifted', 1)->assertJsonPath('data.seat_movements.0.refunded', 1);
    }

    public function test_formatted_phone_call_search_and_self_parent_protection(): void
    {
        $user = $this->agent(); Sanctum::actingAs($user);
        $call = $this->logCall($user, ['caller_phone' => '0300-1234567']);
        $this->getJson(self::BASE . '/calls?search=%2B92%20300%201234567')->assertOk()->assertJsonPath('data.total', 1);
        $this->patchJson(self::BASE . '/calls/' . $call->id, ['category' => 'FU', 'parent_call_id' => $call->id])->assertUnprocessable();
    }

    public function test_permission_migration_and_future_templates_default_roles_to_denied(): void
    {
        $this->agent(1, 1, 10, false, false, false);
        require_once database_path('migrations/2026_10_13_000002_add_call_center_permissions.php');
        (new \AddCallCenterPermissions)->up();
        $company = json_decode(DB::table('companies')->where('id', 1)->value('modules'), true);
        $role = json_decode(DB::table('roles')->where('id', 1)->value('permissions'), true);
        $this->assertTrue($company[0]['allow']);
        $this->assertFalse($role[0]['allow']);
        $this->assertFalse(CallCenterPermissions::append([])[0]['allow']);
        $this->assertSame($role, CallCenterPermissions::append($role));
        (new \AddCallCenterPermissions)->down();
        $this->assertEmpty(json_decode(DB::table('roles')->where('id', 1)->value('permissions'), true)[0]['childs']);
    }

    public function test_supervisor_still_cannot_link_cross_company_or_unassigned_route_bookings(): void
    {
        $user = $this->agent(1, 1, 10, true, true, true, '[1]'); Sanctum::actingAs($user);
        $this->ticket(100, ['route_id' => 2]); $this->ticket(101, ['company_id' => 2, 'terminal_id' => 20]);
        foreach ([100, 101] as $ticket) {
            $this->postJson(self::BASE . '/calls', ['category' => 'BK', 'shift' => 1, 'ticket_id' => $ticket])->assertUnprocessable();
        }
        DB::table('roles')->where('id', 1)->update(['company_id' => 2]); $user->unsetRelation('role');
        $this->getJson(self::BASE . '/options')->assertForbidden();
    }

    public function test_review_cannot_certify_filtered_or_future_reports_and_new_calls_invalidate_review(): void
    {
        $user = $this->agent(1, 1, 10, true); Sanctum::actingAs($user);
        $this->postJson(self::BASE . '/daily-review', ['date' => '2026-10-14'])->assertUnprocessable();
        $this->getJson(self::BASE . '/daily-report?date=2026-10-13&category=GI')->assertUnprocessable();
        $this->getJson(self::BASE . '/daily-report?date=2026-10-13&shift=1')->assertUnprocessable();
        $this->postJson(self::BASE . '/daily-review', ['date' => '2026-10-13', 'snapshot_token' => $this->snapshot()])->assertOk()->assertJsonPath('data.stale', false);
        $this->logCall($user);
        $this->getJson(self::BASE . '/daily-report?date=2026-10-13')->assertOk()->assertJsonPath('data.review.stale', true);
    }

    public function test_callbacks_filter_and_call_pagination_are_scoped(): void
    {
        $user = $this->agent(); Sanctum::actingAs($user);
        $this->postJson(self::BASE . '/calls', ['category' => 'C', 'shift' => 1, 'callback_at' => '2026-10-13 14:00:00'])->assertCreated()->assertJsonPath('data.assigned_to', 1);
        $this->logCall($user, ['status' => 'closed', 'callback_at' => '2026-10-13 14:00:00']);
        for ($i = 0; $i < 26; $i++) { $this->logCall($user); }
        $this->getJson(self::BASE . '/calls?pending_callbacks=1')->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson(self::BASE . '/calls?page=2')->assertOk()->assertJsonPath('data.total', 28)->assertJsonCount(3, 'data.data');
    }

    public function test_short_ticket_numbers_are_searchable_and_zero_is_rejected(): void
    {
        Sanctum::actingAs($this->agent()); $this->ticket(12);
        $this->getJson(self::BASE . '/passengers?search_by=ticket&q=12')->assertOk()->assertJsonPath('data.passengers.0.tickets.0.id', 12);
        $this->getJson(self::BASE . '/passengers?search_by=ticket&q=000')->assertUnprocessable();
    }

    public function test_review_rejects_data_changed_since_report_was_displayed(): void
    {
        $user = $this->agent(1, 1, 10, true); Sanctum::actingAs($user);
        $dailyToken = $this->snapshot(); $shiftToken = $this->snapshot(1);
        $this->logCall($user);
        $this->postJson(self::BASE . '/daily-review', ['date' => '2026-10-13', 'snapshot_token' => $dailyToken])->assertStatus(409)
            ->assertJsonPath('message', 'Report changed. Reload it before reviewing.');
        $this->postJson(self::BASE . '/shift-review', ['date' => '2026-10-13', 'shift' => 1, 'snapshot_token' => $shiftToken])->assertStatus(409);
        $this->assertDatabaseCount('call_center_reviews', 0);
        $this->postJson(self::BASE . '/daily-review', ['date' => '2026-10-13', 'snapshot_token' => $this->snapshot()])->assertOk();
    }

    public function test_report_token_uses_rows_displayed_even_if_new_call_arrives_during_report_loading(): void
    {
        $user = $this->agent(1, 1, 10, true); Sanctum::actingAs($user);
        $this->logCall($user);
        $originalToken = $this->snapshot();
        $insertOnNextRead = true;
        DB::listen(function ($query) use (&$insertOnNextRead, $user) {
            if ($insertOnNextRead && strpos($query->sql, 'select * from "call_center_calls"') === 0) {
                $insertOnNextRead = false;
                $this->logCall($user);
            }
        });
        $response = $this->getJson(self::BASE . '/daily-report?date=2026-10-13')->assertOk();
        $response->assertJsonPath('data.totals.total', 1)->assertJsonPath('data.snapshot_token', $originalToken);
        $this->assertDatabaseCount('call_center_calls', 2);
        $this->postJson(self::BASE . '/daily-review', ['date' => '2026-10-13', 'snapshot_token' => $response->json('data.snapshot_token')])->assertStatus(409);
    }
    public function test_simple_save_outcomes_validate_callbacks_and_preserve_audit(): void
    {
        $user = $this->agent(); Sanctum::actingAs($user);
        $this->postJson(self::BASE . '/calls', ['category' => 'GI', 'inquiry_topic' => 'fare', 'shift' => 2, 'outcome' => 'finish'])
            ->assertCreated()->assertJsonPath('data.status', 'closed')->assertJsonPath('data.inquiry_topic', 'fare');
        $payload = ['category' => 'C', 'shift' => 2, 'remarks' => 'Luggage missing', 'outcome' => 'follow_up', 'caller_phone' => '03001234567'];
        $this->postJson(self::BASE . '/calls', $payload)->assertUnprocessable()->assertJsonValidationErrors('callback_at');
        $this->postJson(self::BASE . '/calls', $payload + ['callback_at' => '2026-10-13 08:59:00'])->assertUnprocessable();
        $this->postJson(self::BASE . '/calls', array_merge($payload, ['callback_at' => '2026-10-13 10:00:00', 'caller_phone' => '']))->assertUnprocessable()->assertJsonValidationErrors('caller_phone');
        $call = $this->postJson(self::BASE . '/calls', $payload + ['callback_at' => '2026-10-13 10:00:00'])->assertCreated()
            ->assertJsonPath('data.status', 'waiting')->assertJsonPath('data.assigned_to', 1)->json('data.id');
        $this->patchJson(self::BASE . '/calls/' . $call, ['outcome' => 'finish'])->assertOk()->assertJsonPath('data.status', 'waiting');
        $this->postJson(self::BASE . '/calls/' . $call . '/close-complaint', ['resolution_remarks' => 'Luggage returned.'])->assertOk()->assertJsonPath('data.status', 'closed')->assertJsonPath('data.callback_at', null);
        $this->assertDatabaseCount('call_center_audits', 3);
        $this->postJson(self::BASE . '/calls', ['category' => 'C', 'shift' => 1, 'outcome' => 'finish'])->assertUnprocessable()->assertJsonValidationErrors('remarks');
    }

    public function test_other_information_topic_is_saved_and_can_be_selected_when_editing(): void
    {
        Sanctum::actingAs($this->agent());
        $call = $this->postJson(self::BASE . '/calls', ['category' => 'GI', 'inquiry_topic' => 'other', 'shift' => 1, 'outcome' => 'finish'])
            ->assertCreated()->assertJsonPath('data.status', 'closed')->assertJsonPath('data.inquiry_topic', 'other')->json('data.id');
        $this->getJson(self::BASE . '/calls/' . $call)->assertOk()->assertJsonPath('data.inquiry_topic', 'other');
        $this->patchJson(self::BASE . '/calls/' . $call, ['inquiry_topic' => 'fare'])->assertOk()->assertJsonPath('data.inquiry_topic', 'fare');
        $this->patchJson(self::BASE . '/calls/' . $call, ['inquiry_topic' => 'other'])->assertOk()->assertJsonPath('data.inquiry_topic', 'other');
        $this->assertDatabaseHas('call_center_calls', ['id' => $call, 'inquiry_topic' => 'other']);
    }

    public function test_work_queues_include_overdue_work_from_earlier_days_and_keep_scope(): void
    {
        $user = $this->agent(); $other = $this->agent(2); Sanctum::actingAs($user);
        $due = $this->logCall($user, ['call_date' => '2026-10-12', 'category' => 'C', 'callback_at' => '2026-10-12 12:00:00']);
        $this->logCall($user, ['category' => 'C', 'callback_at' => '2026-10-13 10:00:00']);
        $this->logCall($user, ['category' => 'C', 'status' => 'closed', 'callback_at' => '2026-10-12 10:00:00']);
        $this->logCall($other, ['category' => 'C', 'callback_at' => '2026-10-12 10:00:00']);
        $this->getJson(self::BASE . '/calls?queue=callbacks_due&date=2026-10-13')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.id', $due->id);
        $this->getJson(self::BASE . '/calls?queue=open_complaints&date=2026-10-13')->assertOk()->assertJsonPath('data.total', 2);
        $this->getJson(self::BASE . '/calls?queue=today')->assertOk()->assertJsonPath('data.total', 2);
        $this->getJson(self::BASE . '/daily-report?date=2026-10-13&queue=open_complaints')->assertUnprocessable();
    }

    public function test_caller_history_matches_normalized_phone_without_exposing_other_agents_or_routes(): void
    {
        $user = $this->agent(1, 1, 10, false, false, true, '[1]'); $other = $this->agent(2); $foreign = $this->agent(3, 2, 20);
        $this->ticket(100); $this->ticket(200, ['route_id' => 2]); Sanctum::actingAs($user);
        $this->logCall($user, ['customer_id' => 100, 'ticket_id' => 100, 'category' => 'C']);
        $this->logCall($user, ['caller_phone' => '+92 300-1234567', 'remarks' => 'Earlier inquiry']);
        $this->logCall($other, ['caller_phone' => '03001234567', 'category' => 'C']);
        $this->logCall($foreign, ['caller_phone' => '03001234567']);
        $this->logCall($user, ['ticket_id' => 200, 'caller_phone' => '03001234567']);
        $this->getJson(self::BASE . '/history?customer_id=100')->assertOk()->assertJsonCount(2, 'data.recent_calls')->assertJsonPath('data.open_complaint_count', 1);
        $this->getJson(self::BASE . '/history?phone=03001234567')->assertOk()->assertJsonCount(1, 'data.recent_calls');
        $this->getJson(self::BASE . '/history?customer_id=200')->assertNotFound();
        $this->getJson(self::BASE . '/history?phone=123')->assertUnprocessable();
    }

    public function test_finishing_followup_resolves_parent_only_when_explicit_and_authorized(): void
    {
        $user = $this->agent(); $other = $this->agent(2); Sanctum::actingAs($user);
        $parent = $this->logCall($user, ['category' => 'C', 'callback_at' => '2026-10-13 10:00:00']);
        $payload = ['category' => 'FU', 'shift' => 1, 'parent_call_id' => $parent->id, 'outcome' => 'finish'];
        $this->postJson(self::BASE . '/calls', $payload)->assertCreated();
        $this->assertSame('new', $parent->fresh()->status);
        $followupId = $this->postJson(self::BASE . '/calls', $payload + ['resolve_parent' => true, 'remarks' => 'Issue resolved with the caller.'])->assertCreated()->json('data.id');
        $this->assertSame('closed', $parent->fresh()->status); $this->assertNull($parent->fresh()->callback_at);
        $this->assertDatabaseHas('call_center_audits', ['call_id' => $parent->id, 'actor_id' => $user->id, 'action' => 'closed']);
        $this->assertSame($followupId, json_decode(DB::table('call_center_audits')->where('call_id', $parent->id)->value('changes'), true)['resolved_by_call']);
        $hidden = $this->logCall($other, ['category' => 'C']);
        $this->postJson(self::BASE . '/calls', array_merge($payload, ['parent_call_id' => $hidden->id, 'resolve_parent' => true]))->assertUnprocessable();
        $this->assertSame('new', $hidden->fresh()->status);
    }

    public function test_optional_complaint_journey_can_be_saved_reopened_edited_and_cleared(): void
    {
        Sanctum::actingAs($this->agent());
        $fields = ['complaint_from_city' => 'Lahore', 'complaint_to_city' => 'Rawalpindi', 'complaint_bus_time' => '18:30', 'complaint_seat_number' => '12'];
        $id = $this->postJson(self::BASE . '/calls', ['category' => 'C', 'shift' => 1, 'remarks' => 'Seat issue', 'outcome' => 'finish'] + $fields)
            ->assertCreated()->assertJsonPath('data.complaint_bus_time', '18:30')->json('data.id');
        $this->getJson(self::BASE . '/calls/' . $id)->assertOk()->assertJsonFragment($fields);
        $this->getJson(self::BASE . '/calls')->assertOk()->assertJsonFragment($fields);
        $this->patchJson(self::BASE . '/calls/' . $id, ['remarks' => 'Updated issue'])->assertOk()->assertJsonFragment($fields);
        $this->patchJson(self::BASE . '/calls/' . $id, ['complaint_seat_number' => '14'])->assertOk()->assertJsonPath('data.complaint_seat_number', '14');
        $audit = json_decode(DB::table('call_center_audits')->where('call_id', $id)->orderByDesc('id')->value('changes'), true);
        $this->assertSame(['from' => '12', 'to' => '14'], $audit['complaint_seat_number']);
        $this->patchJson(self::BASE . '/calls/' . $id, array_fill_keys(array_keys($fields), null))->assertOk()->assertJsonPath('data.complaint_bus_time', null);
        $this->assertDatabaseHas('call_center_calls', ['id' => $id] + array_fill_keys(array_keys($fields), null));
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_complaint_journey_is_optional_and_validated_without_affecting_other_categories(): void
    {
        Sanctum::actingAs($this->agent());
        $base = ['category' => 'C', 'shift' => 1, 'remarks' => 'General complaint', 'outcome' => 'finish'];
        $id = $this->postJson(self::BASE . '/calls', $base)->assertCreated()->json('data.id');
        $this->postJson(self::BASE . '/calls', $base + ['complaint_from_city' => 'Lahore'])->assertCreated();
        $this->postJson(self::BASE . '/calls', $base + ['complaint_bus_time' => '25:00', 'complaint_seat_number' => str_repeat('1', 21), 'complaint_to_city' => str_repeat('x', 121)])
            ->assertUnprocessable()->assertJsonValidationErrors(['complaint_bus_time', 'complaint_seat_number', 'complaint_to_city']);
        $this->patchJson(self::BASE . '/calls/' . $id, ['complaint_bus_time' => '00:00', 'complaint_seat_number' => '12'])->assertOk();
        $this->patchJson(self::BASE . '/calls/' . $id, ['category' => 'GI'])->assertOk()->assertJsonPath('data.complaint_bus_time', null)->assertJsonPath('data.complaint_seat_number', null);
    }

    public function test_confirmed_booking_journey_is_optional_and_saved_without_changing_the_booking(): void
    {
        Sanctum::actingAs($this->agent());
        $this->ticket(100);
        $ticketsBefore = DB::table('tickets')->get()->toJson();
        $base = ['category' => 'BK', 'shift' => 1, 'ticket_id' => 100, 'outcome' => 'finish'];
        $fields = ['complaint_from_city' => 'Lahore', 'complaint_to_city' => 'Rawalpindi', 'complaint_bus_time' => '18:30', 'complaint_seat_number' => '12'];
        $empty = $this->postJson(self::BASE . '/calls', $base)->assertCreated()->json('data.id');
        $this->getJson(self::BASE . '/calls/' . $empty)->assertOk()->assertJsonFragment(array_fill_keys(array_keys($fields), null));
        $id = $this->postJson(self::BASE . '/calls', $base + $fields)->assertCreated()->assertJsonPath('data.seat_count', 1)->json('data.id');
        $this->getJson(self::BASE . '/calls/' . $id)->assertOk()->assertJsonFragment($fields);
        $this->getJson(self::BASE . '/calls')->assertOk()->assertJsonFragment($fields);
        $this->patchJson(self::BASE . '/calls/' . $id, ['remarks' => 'Booking confirmed'])->assertOk()->assertJsonFragment($fields);
        $this->patchJson(self::BASE . '/calls/' . $id, ['complaint_seat_number' => '14', 'seat_count' => 99])->assertOk()
            ->assertJsonPath('data.complaint_seat_number', '14')->assertJsonPath('data.seat_count', 1);
        $audit = json_decode(DB::table('call_center_audits')->where('call_id', $id)->orderByDesc('id')->value('changes'), true);
        $this->assertSame(['from' => '12', 'to' => '14'], $audit['complaint_seat_number']);
        $this->patchJson(self::BASE . '/calls/' . $id, ['complaint_bus_time' => '25:00'])->assertUnprocessable()->assertJsonValidationErrors('complaint_bus_time');
        $this->patchJson(self::BASE . '/calls/' . $id, array_fill_keys(array_keys($fields), null))->assertOk()
            ->assertJsonFragment(array_fill_keys(array_keys($fields), null));
        $this->patchJson(self::BASE . '/calls/' . $id, ['complaint_from_city' => 'Karachi'])->assertOk()->assertJsonPath('data.complaint_from_city', 'Karachi');
        $this->patchJson(self::BASE . '/calls/' . $id, ['category' => 'C', 'status' => 'new'])->assertOk()->assertJsonPath('data.complaint_from_city', 'Karachi');
        $this->patchJson(self::BASE . '/calls/' . $id, ['category' => 'GI'])->assertOk()->assertJsonPath('data.complaint_from_city', null);
        $this->assertSame($ticketsBefore, DB::table('tickets')->get()->toJson());
    }

    public function test_confirmed_booking_call_can_be_saved_and_followed_up_without_a_ticket(): void
    {
        Sanctum::actingAs($this->agent());
        $fields = ['caller_name' => 'Manual caller', 'caller_phone' => '03001234567', 'complaint_from_city' => 'Lahore',
            'complaint_to_city' => 'Karachi', 'complaint_bus_time' => '18:30', 'complaint_seat_number' => '12'];
        $id = $this->postJson(self::BASE . '/calls', ['category' => 'BK', 'shift' => 1, 'outcome' => 'finish', 'seat_count' => 99] + $fields)
            ->assertCreated()->assertJsonPath('data.seat_count', 0)->assertJsonPath('data.status', 'closed')->json('data.id');
        $this->getJson(self::BASE . '/calls/' . $id)->assertOk()->assertJsonFragment($fields)
            ->assertJsonPath('data.ticket_id', null)->assertJsonPath('data.customer_id', null);
        $this->patchJson(self::BASE . '/calls/' . $id, ['remarks' => 'Confirmed by caller', 'outcome' => 'follow_up', 'callback_at' => '2026-10-13 12:00:00'])
            ->assertOk()->assertJsonPath('data.status', 'waiting')->assertJsonPath('data.ticket_id', null)->assertJsonFragment($fields);
        $this->getJson(self::BASE . '/summary?date=2026-10-13')->assertOk()->assertJsonPath('data.totals.BK', 1)->assertJsonPath('data.totals.seats', 0);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_optional_booking_link_can_be_added_and_removed_while_preserving_customer_scope(): void
    {
        Sanctum::actingAs($this->agent());
        $this->ticket(100); $this->ticket(101, ['company_id' => 2, 'terminal_id' => 20]);
        $ticketsBefore = DB::table('tickets')->get()->toJson();
        $id = $this->postJson(self::BASE . '/calls', ['category' => 'BK', 'shift' => 1, 'customer_id' => 100])
            ->assertCreated()->assertJsonPath('data.seat_count', 0)->json('data.id');
        $this->patchJson(self::BASE . '/calls/' . $id, ['ticket_id' => 100])->assertOk()->assertJsonPath('data.seat_count', 1);
        $this->patchJson(self::BASE . '/calls/' . $id, ['ticket_id' => null])->assertOk()
            ->assertJsonPath('data.seat_count', 0)->assertJsonPath('data.customer_id', 100)->assertJsonPath('data.ticket_id', null);
        $this->patchJson(self::BASE . '/calls/' . $id, ['seat_count' => 99])->assertOk()->assertJsonPath('data.seat_count', 0);
        $this->patchJson(self::BASE . '/calls/' . $id, ['customer_id' => 101])->assertUnprocessable()->assertJsonValidationErrors('customer_id');
        $this->patchJson(self::BASE . '/calls/' . $id, ['customer_id' => null, 'outcome' => 'finish'])->assertOk()->assertJsonPath('data.customer_id', null);
        $this->getJson(self::BASE . '/summary?date=2026-10-13')->assertOk()->assertJsonPath('data.totals.seats', 0);
        $this->assertSame($ticketsBefore, DB::table('tickets')->get()->toJson());
    }

    public function test_recorded_complaints_stay_open_until_closed_with_required_remarks(): void
    {
        $user = $this->agent(); Sanctum::actingAs($user);
        foreach (['record', 'finish'] as $outcome) {
            $this->postJson(self::BASE . '/calls', ['category' => 'C', 'shift' => 1, 'remarks' => 'Luggage missing', 'outcome' => $outcome])
                ->assertCreated()->assertJsonPath('data.status', 'new');
        }
        $this->getJson(self::BASE . '/calls?queue=open_complaints')->assertOk()->assertJsonPath('data.total', 2);
        $call = CallLog::first();
        $this->patchJson(self::BASE . '/calls/' . $call->id, ['status' => 'closed'])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->postJson(self::BASE . '/calls', ['category' => 'C', 'shift' => 1, 'remarks' => 'Issue', 'status' => 'closed'])->assertUnprocessable();
        foreach ([[], ['resolution_remarks' => '   '], ['resolution_remarks' => str_repeat('x', 5001)]] as $payload) {
            $this->postJson(self::BASE . '/calls/' . $call->id . '/close-complaint', $payload)->assertUnprocessable()->assertJsonValidationErrors('resolution_remarks');
        }
        $this->assertSame('new', $call->fresh()->status);
        $count = CallLog::count();
        $this->postJson(self::BASE . '/calls', ['category' => 'FU', 'shift' => 1, 'parent_call_id' => $call->id, 'outcome' => 'finish', 'resolve_parent' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('remarks');
        $this->assertSame($count, CallLog::count());
        $this->assertSame('new', $call->fresh()->status);
    }

    public function test_closing_complaint_preserves_description_records_actor_and_is_idempotent(): void
    {
        $owner = $this->agent(); $assignee = $this->agent(2); Sanctum::actingAs($assignee);
        $call = $this->logCall($owner, ['category' => 'C', 'remarks' => 'Luggage missing', 'assigned_to' => 2, 'callback_at' => '2026-10-13 12:00:00']);
        $endpoint = self::BASE . '/calls/' . $call->id . '/close-complaint';
        $this->postJson($endpoint, ['resolution_remarks' => '  Luggage returned to caller.  ', 'resolved_by' => 999])->assertOk()
            ->assertJsonPath('data.status', 'closed')->assertJsonPath('data.remarks', 'Luggage missing')
            ->assertJsonPath('data.resolution_remarks', 'Luggage returned to caller.')->assertJsonPath('data.resolved_by', 2)
            ->assertJsonPath('data.resolved_at', '2026-10-13 09:00:00')->assertJsonPath('data.resolver.name', 'Agent 2')->assertJsonPath('data.callback_at', null);
        $this->postJson($endpoint, ['resolution_remarks' => 'Duplicate click'])->assertOk()->assertJsonPath('data.resolution_remarks', 'Luggage returned to caller.');
        $this->assertDatabaseCount('call_center_audits', 1);
        $audit = json_decode(DB::table('call_center_audits')->value('changes'), true);
        $this->assertSame(['from' => 'new', 'to' => 'closed'], $audit['status']);
        $this->assertSame('Luggage returned to caller.', $audit['resolution_remarks']['to']);
        $this->getJson(self::BASE . '/calls?queue=open_complaints')->assertOk()->assertJsonPath('data.total', 0);
        $this->getJson(self::BASE . '/calls?queue=all&category=C&status=closed')->assertOk()->assertJsonPath('data.data.0.id', $call->id);
        Sanctum::actingAs($owner);
        $this->getJson(self::BASE . '/daily-report?date=2026-10-13')->assertOk()->assertJsonPath('data.complaints.closed', 1)->assertJsonPath('data.complaints.pending', 0);
    }

    public function test_complaint_closure_obeys_scope_and_invalidates_review(): void
    {
        $owner = $this->agent(); $other = $this->agent(2); $foreign = $this->agent(3, 2, 20);
        $hidden = [$this->logCall($other, ['category' => 'C']), $this->logCall($foreign, ['category' => 'C']),
            $this->logCall($owner, ['category' => 'C', 'terminal_id' => 11])];
        Sanctum::actingAs($owner);
        foreach ($hidden as $call) { $this->postJson(self::BASE . '/calls/' . $call->id . '/close-complaint', ['resolution_remarks' => 'Done'])->assertNotFound(); }
        $general = $this->logCall($owner);
        $this->postJson(self::BASE . '/calls/' . $general->id . '/close-complaint', ['resolution_remarks' => 'Done'])->assertUnprocessable();
        $supervisor = $this->agent(4, 1, 10, true); Sanctum::actingAs($supervisor);
        $call = $hidden[0];
        $this->postJson(self::BASE . '/daily-review', ['date' => '2026-10-13', 'snapshot_token' => $this->snapshot()])->assertOk();
        $this->postJson(self::BASE . '/calls/' . $call->id . '/close-complaint', ['resolution_remarks' => 'Resolved by supervisor'])->assertOk();
        $this->getJson(self::BASE . '/daily-report?date=2026-10-13')->assertOk()->assertJsonPath('data.review.stale', true);
    }

    public function test_complaint_updates_preserve_original_details_and_are_visible_without_audit_access(): void
    {
        $owner = $this->agent(); $agent = $this->agent(2); Sanctum::actingAs($agent);
        $this->grant($agent, ['view-complaints', 'update-complaints']);
        $call = $this->logCall($owner, ['category' => 'C', 'status' => 'waiting', 'remarks' => 'Luggage missing', 'assigned_to' => 2, 'callback_at' => '2026-10-13 12:00:00']);
        $before = $call->fresh()->getAttributes();
        $url = self::BASE . '/calls/' . $call->id . '/complaint-updates';
        $this->postJson($url, ['remarks' => '  Contacted the terminal.  ', 'request_id' => (string) \Illuminate\Support\Str::uuid(), 'actor_id' => 999, 'status' => 'closed'])
            ->assertOk()->assertJsonPath('data.status', 'waiting')->assertJsonPath('data.remarks', 'Luggage missing')
            ->assertJsonPath('data.latest_complaint_update.remarks', 'Contacted the terminal.')->assertJsonPath('data.latest_complaint_update.actor.id', 2);
        $this->postJson($url, ['remarks' => 'Terminal is checking the luggage room.', 'request_id' => (string) \Illuminate\Support\Str::uuid()])->assertOk();
        $this->assertSame($before, $call->fresh()->getAttributes());
        $this->assertDatabaseCount('call_center_audits', 2);
        $history = $this->getJson($url)->assertOk()->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.data.0.remarks', 'Terminal is checking the luggage room.')->assertJsonPath('data.data.1.actor.name', 'Agent 2')
            ->assertJsonPath('data.data.1.created_at', '2026-10-13 09:00:00')->json('data.data');
        $this->assertArrayNotHasKey('request_id', $history[0]); $this->assertArrayNotHasKey('changes', $history[0]);
        $this->getJson(self::BASE . '/calls?queue=open_complaints')->assertOk()->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.complaint_history_count', 2)->assertJsonPath('data.data.0.latest_complaint_update.remarks', $history[0]['remarks']);
        $detail = $this->getJson(self::BASE . '/calls/' . $call->id)->assertOk()->json('data');
        $this->assertArrayNotHasKey('audit', $detail);
        $this->postJson(self::BASE . '/calls/' . $call->id . '/close-complaint', ['resolution_remarks' => 'Done'])->assertForbidden();
    }

    public function test_complaint_updates_are_idempotent_and_remain_in_history_after_closure_and_reopening(): void
    {
        $agent = $this->agent(); Sanctum::actingAs($agent);
        $call = $this->logCall($agent, ['category' => 'C', 'remarks' => 'Late bus']);
        $url = self::BASE . '/calls/' . $call->id . '/complaint-updates';
        $payload = ['remarks' => 'Contacted operations.', 'request_id' => (string) \Illuminate\Support\Str::uuid()];
        $this->postJson($url, $payload)->assertOk(); $this->postJson($url, $payload)->assertOk();
        $this->assertDatabaseCount('call_center_audits', 1);
        $this->postJson($url, array_merge($payload, ['remarks' => 'Different note']))->assertStatus(409);
        $this->postJson(self::BASE . '/calls/' . $call->id . '/close-complaint', ['resolution_remarks' => 'Customer informed of the revised departure.'])->assertOk();
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('data.status', 'closed');
        $this->postJson($url, ['remarks' => 'New note', 'request_id' => (string) \Illuminate\Support\Str::uuid()])->assertStatus(409);
        $this->assertDatabaseCount('call_center_audits', 2);
        $this->patchJson(self::BASE . '/calls/' . $call->id, ['status' => 'new'])->assertOk();
        $this->getJson($url)->assertOk()->assertJsonPath('data.total', 2)->assertJsonPath('data.data.0.kind', 'closed')
            ->assertJsonPath('data.data.0.remarks', 'Customer informed of the revised departure.')->assertJsonPath('data.data.1.remarks', 'Contacted operations.');
        $this->getJson(self::BASE . '/calls?queue=open_complaints')->assertOk()->assertJsonPath('data.data.0.complaint_history_count', 2);
    }

    public function test_complaint_update_validation_permissions_and_scope_are_enforced(): void
    {
        $owner = $this->agent(1, 1, 10, false, false, true, '[1]'); $other = $this->agent(2); $foreign = $this->agent(3, 2, 20);
        $this->ticket(200, ['route_id' => 2]); Sanctum::actingAs($owner);
        $call = $this->logCall($owner, ['category' => 'C']); $url = self::BASE . '/calls/' . $call->id . '/complaint-updates';
        $uuid = (string) \Illuminate\Support\Str::uuid();
        foreach ([[], ['remarks' => '   ', 'request_id' => $uuid], ['remarks' => str_repeat('x', 5001), 'request_id' => $uuid], ['remarks' => ['text'], 'request_id' => $uuid], ['remarks' => 'Note'], ['remarks' => 'Note', 'request_id' => 'bad-id']] as $payload) {
            $this->postJson($url, $payload)->assertUnprocessable();
        }
        $hidden = [$this->logCall($other, ['category' => 'C']), $this->logCall($foreign, ['category' => 'C']),
            $this->logCall($owner, ['category' => 'C', 'terminal_id' => 11]), $this->logCall($owner, ['category' => 'C', 'ticket_id' => 200])];
        foreach ($hidden as $hiddenCall) {
            $endpoint = self::BASE . '/calls/' . $hiddenCall->id . '/complaint-updates';
            $this->postJson($endpoint, ['remarks' => 'Note', 'request_id' => $uuid])->assertNotFound();
            $this->getJson($endpoint)->assertNotFound();
        }
        $general = $this->logCall($owner);
        $this->postJson(self::BASE . '/calls/' . $general->id . '/complaint-updates', ['remarks' => 'Note', 'request_id' => $uuid])->assertUnprocessable();
        $this->getJson(self::BASE . '/calls/' . $general->id . '/complaint-updates')->assertUnprocessable();
        $this->grant($owner, ['view-complaints', 'edit-calls', 'close-complaints']);
        $this->postJson($url, ['remarks' => 'Note', 'request_id' => $uuid])->assertForbidden();
        $this->getJson($url)->assertOk();
        $this->grant($owner, ['update-complaints']);
        $this->postJson($url, ['remarks' => 'Note', 'request_id' => $uuid])->assertForbidden(); $this->getJson($url)->assertForbidden();
        $this->assertDatabaseCount('call_center_audits', 0);
    }

    public function test_complaint_history_paginates_and_hides_unrelated_audit_changes(): void
    {
        $agent = $this->agent(); Sanctum::actingAs($agent); $this->grant($agent, ['view-complaints', 'update-complaints']);
        $call = $this->logCall($agent, ['category' => 'C']); $url = self::BASE . '/calls/' . $call->id . '/complaint-updates';
        DB::table('call_center_audits')->insert(['call_id' => $call->id, 'actor_id' => $agent->id, 'action' => 'updated', 'changes' => json_encode(['caller_phone' => ['to' => 'private']]), 'created_at' => '2026-10-13 09:00:00']);
        for ($i = 1; $i <= 26; $i++) {
            DB::table('call_center_audits')->insert(['call_id' => $call->id, 'actor_id' => $agent->id, 'action' => 'complaint_update', 'changes' => json_encode(['remarks' => 'Update ' . $i]), 'created_at' => '2026-10-13 09:00:00']);
        }
        $this->getJson($url)->assertOk()->assertJsonPath('data.total', 26)->assertJsonCount(25, 'data.data')->assertJsonPath('data.data.0.remarks', 'Update 26');
        $this->getJson($url . '?page=2')->assertOk()->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.remarks', 'Update 1');
        $this->getJson($url . '?page=0')->assertUnprocessable();
        $this->getJson(self::BASE . '/calls?queue=open_complaints')->assertOk()->assertJsonPath('data.data.0.complaint_history_count', 26);
    }

    public function test_complaint_update_permission_upgrade_preserves_existing_grants_and_denials(): void
    {
        $agent = $this->agent();
        $permissions = $agent->role->permissions;
        foreach ($permissions as &$module) { if ($module['name'] === 'call-center') $module['buttons'] = array_values(array_filter($module['buttons'], function ($button) { return $button['name'] !== 'call-center-update-complaints'; })); } unset($module);
        DB::table('roles')->where('id', 1)->update(['permissions' => json_encode($permissions)]);
        $this->agent(2); $other = $this->agent(3); $this->grant($other, ['view-complaints']);
        require_once database_path('migrations/2026_10_16_000002_add_call_center_complaint_updates_permission.php');
        (new \AddCallCenterComplaintUpdatesPermission)->up();
        $after = json_decode(DB::table('roles')->where('id', 1)->value('permissions'), true);
        $flags = array_column($after[0]['buttons'], 'allow', 'name');
        $this->assertTrue($flags['call-center-update-complaints']);
        $this->assertFalse(array_column(json_decode(DB::table('roles')->where('id', 3)->value('permissions'), true)[0]['buttons'], 'allow', 'name')['call-center-update-complaints']);
        foreach ($after[0]['buttons'] as &$button) { if ($button['name'] === 'call-center-update-complaints') $button['allow'] = false; } unset($button);
        DB::table('roles')->where('id', 1)->update(['permissions' => json_encode($after)]);
        (new \AddCallCenterComplaintUpdatesPermission)->up();
        $this->assertSame($after, json_decode(DB::table('roles')->where('id', 1)->value('permissions'), true));
        $agent->unsetRelation('role'); $agent->unsetRelation('company'); Sanctum::actingAs($agent);
        $this->getJson(self::BASE . '/options')->assertOk()->assertJsonPath('data.permissions.update_complaints', false);
        $this->assertTrue(array_column(CallCenterPermissions::append([], true)[0]['buttons'], 'allow', 'name')['call-center-update-complaints']);
        $this->assertFalse(array_column(CallCenterPermissions::append([])[0]['buttons'], 'allow', 'name')['call-center-update-complaints']);
    }

    public function test_complaint_history_includes_existing_and_nested_followups_from_other_staff(): void
    {
        $owner = $this->agent(); $reader = $this->agent(2); $other = $this->agent(3);
        $complaint = $this->logCall($owner, ['category' => 'C', 'assigned_to' => $reader->id, 'remarks' => 'Missing luggage']);
        $before = $complaint->fresh()->getAttributes();
        $first = $this->logCall($other, ['category' => 'FU', 'parent_call_id' => $complaint->id, 'status' => 'closed', 'remarks' => 'Customer requested an update.', 'called_at' => '2026-10-13 10:00:00']);
        $second = $this->logCall($other, ['category' => 'FU', 'parent_call_id' => $first->id, 'status' => 'waiting', 'remarks' => 'Terminal will call the customer.', 'called_at' => '2026-10-13 12:00:00', 'callback_at' => '2026-10-13 15:00:00']);
        DB::table('call_center_audits')->insert(['call_id' => $complaint->id, 'actor_id' => $owner->id, 'action' => 'complaint_update', 'changes' => json_encode(['remarks' => 'Contacted the terminal.']), 'created_at' => '2026-10-13 11:00:00']);
        $this->grant($reader, ['view-complaints']); Sanctum::actingAs($reader);
        $history = $this->getJson(self::BASE . '/calls/' . $complaint->id . '/complaint-updates')->assertOk()->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.data.0.kind', 'follow_up')->assertJsonPath('data.data.0.id', 'follow-up-' . $second->id)
            ->assertJsonPath('data.data.0.reference', $second->reference)->assertJsonPath('data.data.0.remarks', $second->remarks)
            ->assertJsonPath('data.data.0.actor.name', $other->name)->assertJsonPath('data.data.0.created_at', '2026-10-13 12:00:00')
            ->assertJsonPath('data.data.0.callback_at', '2026-10-13 15:00:00')->assertJsonPath('data.data.1.kind', 'update')
            ->assertJsonPath('data.data.2.call_id', $first->id)->assertJsonPath('data.data.2.callback_at', null)->json('data.data');
        $this->getJson(self::BASE . '/calls/' . $first->id)->assertNotFound();
        $this->getJson(self::BASE . '/calls?queue=open_complaints')->assertOk()->assertJsonPath('data.data.0.complaint_history_count', 3);
        $this->getJson(self::BASE . '/calls/' . $complaint->id)->assertOk()->assertJsonPath('data.complaint_history_count', 3)
            ->assertJsonPath('data.latest_complaint_update.remarks', 'Contacted the terminal.');
        $this->assertSame($before, $complaint->fresh()->getAttributes());
        $this->assertDatabaseCount('call_center_audits', 1);
        foreach (['caller_phone', 'ticket_id', 'customer_id', 'changes'] as $private) { $this->assertArrayNotHasKey($private, $history[0]); }
    }

    public function test_followup_saved_once_appears_in_complaint_history_and_edits_do_not_duplicate_it(): void
    {
        $agent = $this->agent(); Sanctum::actingAs($agent);
        $complaint = $this->logCall($agent, ['category' => 'C', 'remarks' => 'Late departure']);
        $id = $this->postJson(self::BASE . '/calls', ['category' => 'FU', 'parent_call_id' => $complaint->id, 'shift' => 1,
            'remarks' => 'Customer asked for an update.', 'outcome' => 'finish'])->assertCreated()->json('data.id');
        $history = self::BASE . '/calls/' . $complaint->id . '/complaint-updates';
        $this->getJson($history)->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.remarks', 'Customer asked for an update.');
        $this->patchJson(self::BASE . '/calls/' . $id, ['remarks' => 'Customer informed of the new departure time.'])->assertOk();
        $this->getJson($history)->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.remarks', 'Customer informed of the new departure time.');
        $this->assertSame('new', $complaint->fresh()->status);
        $this->assertSame(0, DB::table('call_center_audits')->where('call_id', $complaint->id)->count());
        Carbon::setTestNow(Carbon::parse('2026-10-13 10:00:00', 'Asia/Karachi'));
        $second = $this->postJson(self::BASE . '/calls', ['category' => 'FU', 'parent_call_id' => $id, 'shift' => 1,
            'remarks' => 'Promised a callback.', 'caller_phone' => '03001234567', 'callback_at' => '2026-10-13 15:00:00', 'outcome' => 'follow_up'])->assertCreated()->json('data.id');
        $this->getJson($history)->assertJsonPath('data.total', 2)->assertJsonPath('data.data.0.callback_at', '2026-10-13 15:00:00');
        $this->patchJson(self::BASE . '/calls/' . $second, ['outcome' => 'finish'])->assertOk();
        $this->getJson($history)->assertJsonPath('data.total', 2)->assertJsonPath('data.data.0.callback_at', null);
        $this->postJson(self::BASE . '/calls/' . $complaint->id . '/close-complaint', ['resolution_remarks' => 'Resolved.'])->assertOk();
        $this->getJson($history)->assertJsonPath('data.total', 3)->assertJsonPath('data.data.0.kind', 'closed')->assertJsonPath('data.data.1.kind', 'follow_up');
        $this->patchJson(self::BASE . '/calls/' . $complaint->id, ['status' => 'new'])->assertOk();
        $this->getJson($history)->assertJsonPath('data.total', 3);
        $this->getJson(self::BASE . '/daily-report?date=2026-10-13')->assertOk()->assertJsonPath('data.totals.FU', 2)->assertJsonPath('data.totals.C', 1);
    }

    public function test_related_history_preserves_company_terminal_route_and_parent_access_boundaries(): void
    {
        $agent = $this->agent(1, 1, 10, false, false, true, '[1]'); $other = $this->agent(2); $foreign = $this->agent(3, 2, 20);
        $this->ticket(200, ['route_id' => 2]);
        $complaint = $this->logCall($agent, ['category' => 'C']);
        $visible = $this->logCall($other, ['category' => 'FU', 'parent_call_id' => $complaint->id]);
        $this->logCall($foreign, ['category' => 'FU', 'parent_call_id' => $complaint->id]);
        $hidden = $this->logCall($other, ['category' => 'FU', 'parent_call_id' => $complaint->id, 'terminal_id' => 11]);
        $this->logCall($other, ['category' => 'FU', 'parent_call_id' => $hidden->id]);
        $this->logCall($other, ['category' => 'FU', 'parent_call_id' => $complaint->id, 'ticket_id' => 200]);
        $this->logCall($other, ['category' => 'GI', 'parent_call_id' => $complaint->id]);
        $unrelated = $this->logCall($other, ['category' => 'C']);
        $this->logCall($agent, ['category' => 'FU', 'parent_call_id' => $unrelated->id]);
        Sanctum::actingAs($agent); $this->grant($agent, ['view-complaints']);
        $this->getJson(self::BASE . '/calls/' . $complaint->id . '/complaint-updates')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.call_id', $visible->id);
        $this->getJson(self::BASE . '/calls/' . $complaint->id)->assertJsonPath('data.complaint_history_count', 1);
        $this->getJson(self::BASE . '/calls/' . $unrelated->id . '/complaint-updates')->assertNotFound();
        $this->grant($agent, ['view-calls']);
        $this->getJson(self::BASE . '/calls/' . $complaint->id . '/complaint-updates')->assertForbidden();
    }

    public function test_mixed_complaint_history_paginates_in_time_order_with_distinct_entry_ids(): void
    {
        $agent = $this->agent(); Sanctum::actingAs($agent); $complaint = $this->logCall($agent, ['category' => 'C']);
        for ($i = 1; $i <= 26; $i++) {
            DB::table('call_center_audits')->insert(['id' => $i, 'call_id' => $complaint->id, 'actor_id' => $agent->id,
                'action' => 'complaint_update', 'changes' => json_encode(['remarks' => 'Note ' . $i]), 'created_at' => sprintf('2026-10-13 09:%02d:00', $i)]);
        }
        $followup = $this->logCall($agent, ['category' => 'FU', 'parent_call_id' => $complaint->id, 'called_at' => '2026-10-13 09:10:30', 'remarks' => 'Follow-up conversation']);
        $endpoint = self::BASE . '/calls/' . $complaint->id . '/complaint-updates';
        $first = $this->getJson($endpoint)->assertOk()->assertJsonPath('data.total', 27)->assertJsonCount(25, 'data.data')->json('data.data');
        $second = $this->getJson($endpoint . '?page=2')->assertOk()->assertJsonCount(2, 'data.data')->json('data.data');
        $all = array_merge($first, $second);
        $this->assertCount(27, array_unique(array_column($all, 'id')));
        $this->assertSame('follow-up-' . $followup->id, $all[16]['id']);
        $this->assertSame('Note 10', $all[17]['remarks']);
        $this->assertSame('Note 1', $all[26]['remarks']);
        $this->getJson(self::BASE . '/calls?queue=open_complaints')->assertJsonPath('data.data.0.complaint_history_count', 27);
    }

    private function informationSchema(): void
    {
        Schema::table('cities', function (Blueprint $t) { $t->integer('company_id')->default(1); $t->integer('hide')->default(0); });
        Schema::table('terminals', function (Blueprint $t) { $t->integer('city_id')->default(1); $t->string('address')->default('Terminal address'); $t->string('contact')->default('03000000000'); $t->string('available_seats')->nullable(); });
        Schema::table('users', function (Blueprint $t) { $t->boolean('check_allowed_seats')->default(false); });
        Schema::table('tickets', function (Blueprint $t) { $t->integer('schedule_id')->nullable(); });
        Schema::create('routes_fares', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->integer('route_id'); $t->integer('departure_city_id'); $t->integer('destination_city_id'); $t->softDeletes(); });
        Schema::create('schedules', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->integer('route_id'); $t->string('name'); $t->integer('hide')->default(0); $t->softDeletes(); });
        Schema::create('schedule_details', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->integer('schedule_id'); $t->integer('bus_class_id'); $t->integer('departure_id'); $t->integer('destination_id'); $t->date('departure_date'); $t->date('schedule_date'); $t->time('departure_time'); $t->softDeletes(); });
        Schema::create('schedule_terminal_visibilities', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->integer('schedule_id'); $t->integer('terminal_id'); $t->integer('visibility')->default(1); $t->softDeletes(); });
        Schema::create('drop_schedules', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->integer('schedule_id'); $t->date('schedule_date'); $t->softDeletes(); });
        Schema::create('bus_classes', function (Blueprint $t) { $t->id(); $t->string('name'); $t->text('seat_map')->nullable(); $t->softDeletes(); });
        Schema::create('fare_classes', function (Blueprint $t) { $t->id(); $t->string('name'); $t->softDeletes(); });
        Schema::create('fare_tables', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->integer('fare_class'); $t->integer('from_city_id'); $t->integer('to_city_id'); $t->decimal('fare'); $t->softDeletes(); });
        Schema::create('terminal_time_differences', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->integer('terminal_id'); $t->integer('route_id'); $t->integer('time_difference'); $t->softDeletes(); });
        $seats = array_map(function ($number) { return ['seatNo' => $number, 'reserved' => true, 'type' => 0]; }, range(1, 6));
        $seats[] = ['reserved' => false, 'type' => 0];
        $seats[] = ['seatNo' => 7, 'reserved' => true, 'type' => 'not_for_sale'];
        DB::table('bus_classes')->insert(['id' => 1, 'name' => 'Executive', 'seat_map' => json_encode([$seats])]); DB::table('fare_classes')->insert(['id' => 1, 'name' => 'Standard']);
        DB::table('routes_fares')->insert([['company_id' => 1, 'route_id' => 1, 'departure_city_id' => 1, 'destination_city_id' => 2], ['company_id' => 1, 'route_id' => 2, 'departure_city_id' => 1, 'destination_city_id' => 2]]);
        DB::table('fare_tables')->insert(['company_id' => 1, 'fare_class' => 1, 'from_city_id' => 1, 'to_city_id' => 2, 'fare' => 2400]);
        DB::table('terminal_time_differences')->insert(['company_id' => 1, 'terminal_id' => 10, 'route_id' => 1, 'time_difference' => 30]);
        for ($id = 1; $id <= 5; $id++) {
            DB::table('schedules')->insert(['id' => $id, 'company_id' => 1, 'route_id' => $id === 2 ? 2 : 1, 'name' => 'Schedule ' . $id, 'hide' => $id === 3 ? 1 : 0]);
            DB::table('schedule_details')->insert(['id' => $id, 'company_id' => 1, 'schedule_id' => $id, 'bus_class_id' => 1, 'departure_id' => 1, 'destination_id' => 2, 'departure_date' => '2026-10-13', 'schedule_date' => '2026-10-13', 'departure_time' => '12:00:00']);
            DB::table('schedule_terminal_visibilities')->insert(['company_id' => 1, 'schedule_id' => $id, 'terminal_id' => 10, 'visibility' => $id === 5 ? 0 : 1]);
        }
        DB::table('drop_schedules')->insert(['company_id' => 1, 'schedule_id' => 4, 'schedule_date' => '2026-10-13']);
    }

    public function test_terminal_contact_directory_spans_company_cities_without_expanding_booking_access(): void
    {
        $this->informationSchema(); $user = $this->agent(); Sanctum::actingAs($user);
        DB::table('terminals')->where('id', 11)->update(['city_id' => 2]);
        DB::table('terminals')->insert(['id' => 12, 'company_id' => 1, 'city_id' => 2, 'name' => 'Hidden terminal', 'hide' => 1]);
        $response = $this->getJson(self::BASE . '/quick-answers/options')->assertOk()->assertJsonCount(1, 'data.terminals')->assertJsonCount(2, 'data.terminal_directory');
        $this->assertEqualsCanonicalizing([10, 11], array_column($response->json('data.terminal_directory'), 'id'));
        $this->getJson(self::BASE . '/quick-answers?departure_id=1&destination_id=2&date=2026-10-13&terminal_id=11')->assertForbidden();
        DB::table('cities')->where('id', 2)->update(['hide' => 1]);
        $this->getJson(self::BASE . '/quick-answers/options')->assertJsonCount(1, 'data.terminal_directory');
        $this->grant($user, ['view-calls']);
        $this->getJson(self::BASE . '/quick-answers/options')->assertForbidden();
    }

    public function test_quick_answers_use_scoped_schedules_existing_base_fares_and_terminal_details(): void
    {
        $this->informationSchema(); $user = $this->agent(1, 1, 10, false, false, true, '[1]'); Sanctum::actingAs($user);
        $this->getJson(self::BASE . '/quick-answers/options')->assertOk()->assertJsonCount(1, 'data.terminals')->assertJsonPath('data.terminals.0.address', 'Terminal address');
        $url = self::BASE . '/quick-answers?departure_id=1&destination_id=2&date=2026-10-13';
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'data.departures')->assertJsonPath('data.departures.0.id', 1)
            ->assertJsonPath('data.departures.0.departure_at', '2026-10-13 12:30:00')->assertJsonPath('data.fares.0.class_name', 'Standard')
            ->assertJsonPath('data.departures.0.available_seats', 6);
        $this->getJson($url . '&terminal_id=11')->assertForbidden();
        $this->getJson(str_replace('destination_id=2', 'destination_id=999', $url))->assertUnprocessable();
        $user->update(['route_ids' => 'invalid']);
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'data.departures')->assertJsonCount(0, 'data.fares');
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_available_seats_count_unique_occupied_seats_for_each_service_across_terminals(): void
    {
        $this->informationSchema(); Sanctum::actingAs($this->agent());
        $trip = ['schedule_id' => 1, 'schedule_date' => '2026-10-13'];
        $this->ticket(100, $trip + ['seat_no' => 1]);
        $this->ticket(101, $trip + ['seat_no' => 2, 'type' => 'advance booking', 'terminal_id' => 11]);
        $this->ticket(102, $trip + ['seat_no' => 1, 'type' => 'over-issue']);
        $this->ticket(103, $trip + ['seat_no' => 3, 'type' => 'canceled', 'deleted_at' => '2026-10-13 08:00:00']);
        $this->ticket(104, ['schedule_id' => 1, 'schedule_date' => '2026-10-14', 'seat_no' => 4]);
        $this->ticket(105, $trip + ['seat_no' => 5, 'company_id' => 2, 'terminal_id' => 20]);
        $this->ticket(106, $trip + ['seat_no' => 99, 'type' => 'over-issue']);
        $this->ticket(107, $trip + ['seat_no' => 3, 'type' => 'not_for_sale']);
        $this->ticket(108, ['schedule_id' => 2, 'schedule_date' => '2026-10-13', 'seat_no' => 1]);
        $response = $this->getJson(self::BASE . '/quick-answers?departure_id=1&destination_id=2&date=2026-10-13')->assertOk();
        $counts = collect($response->json('data.departures'))->pluck('available_seats', 'id')->all();
        $this->assertSame([1 => 3, 2 => 5], $counts);
        $this->assertDatabaseCount('tickets', 9); // Availability is read-only.
    }

    public function test_available_seats_respect_partial_journeys_and_overnight_service_dates(): void
    {
        $this->informationSchema(); Sanctum::actingAs($this->agent(1, 1, 10, false, false, true, '[1]'));
        DB::table('cities')->insert([['id' => 3, 'name' => 'Stop B', 'company_id' => 1], ['id' => 4, 'name' => 'Stop C', 'company_id' => 1]]);
        DB::table('routes_fares')->where('route_id', 1)->delete();
        foreach ([[1, 3], [1, 4], [1, 2], [3, 4], [3, 2], [4, 2]] as $pair) {
            DB::table('routes_fares')->insert(['company_id' => 1, 'route_id' => 1, 'departure_city_id' => $pair[0], 'destination_city_id' => $pair[1]]);
        }
        DB::table('schedule_details')->where('id', 1)->update(['departure_id' => 3, 'destination_id' => 4, 'departure_date' => '2026-10-14']);
        $trip = ['schedule_id' => 1, 'schedule_date' => '2026-10-13', 'is_partial' => true];
        $this->ticket(100, $trip + ['seat_no' => 1, 'departure_city_id' => 1, 'destination_city_id' => 3]);
        $this->ticket(101, $trip + ['seat_no' => 1, 'departure_city_id' => 4, 'destination_city_id' => 2]);
        $this->ticket(102, $trip + ['seat_no' => 2, 'departure_city_id' => 1, 'destination_city_id' => 4]);
        $this->ticket(103, $trip + ['seat_no' => 3, 'departure_city_id' => 3, 'destination_city_id' => 2]);
        $this->ticket(104, $trip + ['seat_no' => 4, 'departure_city_id' => 1, 'destination_city_id' => 2]);
        $this->ticket(105, $trip + ['seat_no' => 5, 'departure_city_id' => 3, 'destination_city_id' => 4]);
        $this->ticket(106, ['schedule_id' => 1, 'schedule_date' => '2026-10-14', 'seat_no' => 6]);
        $this->getJson(self::BASE . '/quick-answers?departure_id=3&destination_id=4&date=2026-10-14')
            ->assertOk()->assertJsonPath('data.departures.0.available_seats', 2);
    }

    public function test_available_seats_honor_terminal_seat_restrictions_and_zero_availability(): void
    {
        $this->informationSchema(); $user = $this->agent(1, 1, 10, false, false, true, '[1]');
        $user->update(['check_allowed_seats' => true]); Sanctum::actingAs($user);
        DB::table('terminals')->where('id', 10)->update(['available_seats' => '1-2,4|6']);
        $url = self::BASE . '/quick-answers?departure_id=1&destination_id=2&date=2026-10-13';
        $this->getJson($url)->assertOk()->assertJsonPath('data.departures.0.available_seats', 4);
        foreach ([1, 2, 4, 6] as $seat) { $this->ticket(100 + $seat, ['schedule_id' => 1, 'schedule_date' => '2026-10-13', 'seat_no' => $seat]); }
        $this->getJson($url)->assertOk()->assertJsonPath('data.departures.0.available_seats', 0);
        $user->update(['check_allowed_seats' => false]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.departures.0.available_seats', 2);
    }

    public function test_available_seats_are_unknown_when_map_or_journey_data_cannot_be_verified(): void
    {
        $this->informationSchema(); Sanctum::actingAs($this->agent(1, 1, 10, false, false, true, '[1]'));
        $url = self::BASE . '/quick-answers?departure_id=1&destination_id=2&date=2026-10-13';
        $this->ticket(100, ['schedule_id' => 1, 'schedule_date' => '2026-10-13', 'seat_no' => 1, 'departure_city_id' => 999]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.departures.0.available_seats', null);
        DB::table('bus_classes')->where('id', 1)->update(['seat_map' => null]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.departures.0.available_seats', null);
    }

    private function grant(User $user, array $actions): void
    {
        $permissions = CallCenterPermissions::append([], true);
        foreach ($permissions[0]['buttons'] as &$button) {
            $button['allow'] = in_array(substr($button['name'], 12), $actions, true);
        }
        unset($button);
        DB::table('roles')->where('id', $user->role_id)->update(['permissions' => json_encode($permissions)]);
        $user->unsetRelation('role');
    }

    public function test_scope_grants_never_imply_actions_and_missing_flags_deny(): void
    {
        $user = $this->agent(); Sanctum::actingAs($user);
        $call = $this->logCall($user, ['category' => 'C', 'remarks' => 'Complaint']);
        $this->grant($user, ['supervise', 'all-terminals']);
        $this->getJson(self::BASE . '/options')->assertOk()->assertJsonPath('data.permissions.create', false)
            ->assertJsonPath('data.permissions.review', false)->assertJsonPath('data.permissions.supervise', true);
        foreach (['/calls', '/calls/' . $call->id, '/history?phone=03001234567', '/passengers?search_by=phone&q=03001234567',
            '/quick-answers/options', '/quick-answers', '/summary?date=2026-10-13', '/daily-report?date=2026-10-13'] as $path) {
            $this->getJson(self::BASE . $path)->assertForbidden();
        }
        $this->postJson(self::BASE . '/calls', ['category' => 'GI', 'shift' => 1])->assertForbidden();
        $this->patchJson(self::BASE . '/calls/' . $call->id, ['remarks' => 'Changed'])->assertForbidden();
        $this->postJson(self::BASE . '/calls/' . $call->id . '/close-complaint', ['resolution_remarks' => 'Resolved'])->assertForbidden();
        foreach (['shift-review', 'daily-review'] as $path) { $this->postJson(self::BASE . '/' . $path, [])->assertForbidden(); }
        $legacy = [['name' => 'ticketing', 'allow' => true, 'childs' => [['name' => 'call-center', 'allow' => true, 'buttons' => []]]]];
        DB::table('roles')->where('id', $user->role_id)->update(['permissions' => json_encode($legacy)]); $user->unsetRelation('role');
        $this->postJson(self::BASE . '/calls', ['category' => 'GI', 'shift' => 1])->assertForbidden();
    }

    public function test_complaint_view_and_closure_are_independent_of_call_editing(): void
    {
        $user = $this->agent(); Sanctum::actingAs($user);
        $ordinary = $this->logCall($user); $complaint = $this->logCall($user, ['category' => 'C', 'remarks' => 'Late bus']);
        $this->grant($user, ['view-complaints']);
        $this->getJson(self::BASE . '/calls')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.id', $complaint->id);
        $this->getJson(self::BASE . '/calls?category=GI')->assertOk()->assertJsonPath('data.total', 0);
        $this->getJson(self::BASE . '/calls/' . $ordinary->id)->assertForbidden();
        $this->assertArrayNotHasKey('audit', $this->getJson(self::BASE . '/calls/' . $complaint->id)->assertOk()->json('data'));
        $this->postJson(self::BASE . '/calls/' . $complaint->id . '/close-complaint', ['resolution_remarks' => 'Resolved'])->assertForbidden();
        $this->grant($user, ['view-complaints', 'close-complaints']);
        $this->postJson(self::BASE . '/calls/' . $complaint->id . '/close-complaint', ['resolution_remarks' => 'Resolved'])->assertOk()->assertJsonPath('data.status', 'closed');
        $this->patchJson(self::BASE . '/calls/' . $complaint->id, ['status' => 'new'])->assertForbidden();
        $this->grant($user, ['view-complaints', 'edit-calls', 'close-complaints']);
        $this->patchJson(self::BASE . '/calls/' . $complaint->id, ['category' => 'GI', 'status' => 'new'])->assertForbidden();
        $this->grant($user, ['view-complaints', 'edit-calls']);
        $this->patchJson(self::BASE . '/calls/' . $complaint->id, ['status' => 'new'])->assertForbidden();
        $this->grant($user, ['view-complaints', 'edit-calls', 'reopen-complaints', 'view-audit']);
        $this->patchJson(self::BASE . '/calls/' . $complaint->id, ['status' => 'new'])->assertOk()->assertJsonPath('data.resolved_by', null);
        $this->getJson(self::BASE . '/calls/' . $complaint->id)->assertOk()->assertJsonCount(2, 'data.audit');
    }

    public function test_create_permission_does_not_allow_lookup_assignment_callbacks_or_followups(): void
    {
        $user = $this->agent(); $other = $this->agent(2); Sanctum::actingAs($user); $this->ticket();
        $this->grant($user, ['create-calls']);
        $base = ['category' => 'GI', 'shift' => 1, 'caller_phone' => '03001234567'];
        $id = $this->postJson(self::BASE . '/calls', $base)->assertCreated()->json('data.id');
        $this->getJson(self::BASE . '/calls/' . $id)->assertForbidden();
        $this->postJson(self::BASE . '/calls', $base + ['ticket_id' => 100])->assertForbidden();
        $this->postJson(self::BASE . '/calls', $base + ['assigned_to' => $other->id])->assertForbidden();
        $this->postJson(self::BASE . '/calls', $base + ['callback_at' => '2026-10-13 12:00:00'])->assertForbidden();
        $this->postJson(self::BASE . '/calls', ['category' => 'FU', 'shift' => 1, 'parent_call_id' => $id])->assertForbidden();
        $this->assertSame(1, CallLog::count());
        $this->grant($user, ['create-calls', 'passenger-lookup', 'assign-calls', 'manage-callbacks']);
        $this->postJson(self::BASE . '/calls', $base + ['ticket_id' => 100, 'assigned_to' => $other->id, 'callback_at' => '2026-10-13 12:00:00', 'outcome' => 'follow_up'])
            ->assertCreated()->assertJsonPath('data.status', 'waiting');
    }

    public function test_edit_permission_cannot_bypass_callback_assignment_or_complaint_closure(): void
    {
        $user = $this->agent(); $other = $this->agent(2); Sanctum::actingAs($user);
        $call = $this->logCall($user, ['assigned_to' => 2, 'callback_at' => '2026-10-13 12:00:00']);
        $complaint = $this->logCall($user, ['category' => 'C']);
        $this->grant($user, ['view-calls', 'edit-calls']);
        $this->patchJson(self::BASE . '/calls/' . $call->id, ['remarks' => 'Notes only'])->assertOk();
        $this->patchJson(self::BASE . '/calls/' . $call->id, ['callback_at' => '2026-10-13 13:00:00'])->assertForbidden();
        $this->patchJson(self::BASE . '/calls/' . $call->id, ['assigned_to' => 1])->assertForbidden();
        $this->patchJson(self::BASE . '/calls/' . $call->id, ['assigned_to' => null])->assertForbidden();
        $this->patchJson(self::BASE . '/calls/' . $complaint->id, ['category' => 'GI', 'outcome' => 'finish'])->assertForbidden();
        $this->assertSame('new', $complaint->fresh()->status);
        $this->grant($user, ['view-calls', 'edit-calls', 'view-complaints', 'close-complaints']);
        $this->patchJson(self::BASE . '/calls/' . $complaint->id, ['category' => 'GI', 'outcome' => 'finish'])->assertUnprocessable();
    }

    public function test_followup_cannot_close_parent_without_the_parent_action_permission(): void
    {
        $user = $this->agent(); Sanctum::actingAs($user);
        $parent = $this->logCall($user, ['category' => 'C']);
        $actions = ['create-calls', 'view-calls', 'follow-up', 'view-complaints'];
        $this->grant($user, $actions);
        $payload = ['category' => 'FU', 'shift' => 1, 'parent_call_id' => $parent->id, 'resolve_parent' => true, 'outcome' => 'finish', 'remarks' => 'Resolved by terminal'];
        $this->postJson(self::BASE . '/calls', $payload)->assertForbidden();
        $this->assertSame(1, CallLog::count()); $this->assertSame('new', $parent->fresh()->status);
        $this->grant($user, array_merge($actions, ['close-complaints']));
        $this->postJson(self::BASE . '/calls', $payload)->assertCreated();
        $this->assertSame('closed', $parent->fresh()->status);
        $ordinary = $this->logCall($user);
        $payload['parent_call_id'] = $ordinary->id;
        $this->postJson(self::BASE . '/calls', $payload)->assertForbidden();
        $this->assertSame('new', $ordinary->fresh()->status);
    }

    public function test_report_access_review_and_scope_are_separate(): void
    {
        $user = $this->agent(); $other = $this->agent(2); Sanctum::actingAs($user);
        $this->logCall($user); $this->logCall($other);
        $this->grant($user, ['shift-report']);
        $this->getJson(self::BASE . '/summary?date=2026-10-13')->assertOk()->assertJsonPath('data.totals.total', 1);
        $this->getJson(self::BASE . '/daily-report?date=2026-10-13')->assertForbidden();
        $this->grant($user, ['shift-report', 'supervise']);
        $token = $this->getJson(self::BASE . '/summary?date=2026-10-13')->assertOk()->assertJsonPath('data.totals.total', 2)->json('data.snapshot_tokens.1');
        $payload = ['date' => '2026-10-13', 'shift' => 1, 'snapshot_token' => $token];
        $this->postJson(self::BASE . '/shift-review', $payload)->assertForbidden();
        $this->grant($user, ['shift-report', 'review-reports']);
        $this->postJson(self::BASE . '/shift-review', $payload)->assertForbidden();
        $this->grant($user, ['shift-report', 'supervise', 'review-reports']);
        $this->postJson(self::BASE . '/shift-review', $payload)->assertOk();
        $this->postJson(self::BASE . '/daily-review', $payload)->assertForbidden();
        $this->getJson(self::BASE . '/calls')->assertForbidden();
    }

    public function test_company_module_and_action_denials_override_role_grants(): void
    {
        $user = $this->agent(); Sanctum::actingAs($user);
        $company = CallCenterPermissions::append([], true);
        foreach ($company[0]['buttons'] as &$button) { if ($button['name'] === 'call-center-create-calls') $button['allow'] = false; }
        unset($button);
        DB::table('companies')->where('id', 1)->update(['modules' => json_encode($company)]); $user->unsetRelation('company');
        $this->getJson(self::BASE . '/options')->assertOk()->assertJsonPath('data.permissions.create', false);
        $this->postJson(self::BASE . '/calls', ['category' => 'GI', 'shift' => 1])->assertForbidden();
        $company[0]['allow'] = false;
        DB::table('companies')->where('id', 1)->update(['modules' => json_encode($company)]); $user->unsetRelation('company');
        $this->getJson(self::BASE . '/options')->assertForbidden();
    }

    public function test_granular_migration_preserves_legacy_access_and_never_regrants_denials(): void
    {
        $legacy = [['name' => 'ticketing', 'allow' => true, 'childs' => [['name' => 'call-center', 'allow' => true, 'buttons' => [
            ['name' => 'call-center-supervise', 'allow' => false], ['name' => 'call-center-all-terminals', 'allow' => false],
        ]]]]];
        DB::table('roles')->insert(['id' => 1, 'company_id' => 1, 'permissions' => json_encode($legacy)]);
        DB::table('roles')->insert(['id' => 2, 'company_id' => 1, 'permissions' => '[]']);
        require_once database_path('migrations/2026_10_15_000005_add_call_center_role_privileges.php');
        (new \AddCallCenterRolePrivileges)->up();
        $role = json_decode(DB::table('roles')->where('id', 1)->value('permissions'), true);
        $flags = array_column($role[0]['buttons'], 'allow', 'name');
        $this->assertCount(19, $flags); $this->assertTrue($flags['call-center-close-complaints']);
        $this->assertFalse($flags['call-center-review-reports']); $this->assertFalse($flags['call-center-supervise']);
        $new = json_decode(DB::table('roles')->where('id', 2)->value('permissions'), true);
        $this->assertFalse($new[0]['allow']);
        $this->assertSame([], array_filter(array_column($new[0]['buttons'], 'allow')));
        foreach ($role[0]['buttons'] as &$button) { if ($button['name'] === 'call-center-create-calls') $button['allow'] = false; }
        unset($button);
        $role[0]['buttons'] = array_values(array_filter($role[0]['buttons'], function ($button) { return $button['name'] !== 'call-center-view-audit'; }));
        DB::table('roles')->where('id', 1)->update(['permissions' => json_encode($role)]);
        (new \AddCallCenterRolePrivileges)->up();
        $after = DB::table('roles')->where('id', 1)->value('permissions');
        $flags = array_column(json_decode($after, true)[0]['buttons'], 'allow', 'name');
        $this->assertFalse($flags['call-center-create-calls']); $this->assertFalse($flags['call-center-view-audit']);
        (new \AddCallCenterRolePrivileges)->up();
        $this->assertSame($after, DB::table('roles')->where('id', 1)->value('permissions'));
    }


    public function test_role_editor_defaults_and_saves_granular_grants_with_company_boundary(): void
    {
        // Legacy routes use require_once, so register this file for the rebuilt test application.
        \Illuminate\Support\Facades\Route::middleware('api')->prefix('api')->group(function () { require base_path('routes/api/role.php'); });
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id(); $t->integer('activity_by'); $t->text('message'); $t->string('requested_host'); $t->integer('company_id'); $t->timestamps(); $t->softDeletes();
        });
        $user = $this->agent(); Sanctum::actingAs($user);
        $permissions = $user->role->permissions;
        $permissions[] = ['name' => 'users', 'allow' => true, 'childs' => [['name' => 'roles', 'allow' => true, 'buttons' => [['name' => 'edit-role', 'allow' => true]]]]];
        DB::table('roles')->where('id', 1)->update(['permissions' => json_encode($permissions)]); $user->unsetRelation('role');
        DB::table('roles')->insert(['id' => 2, 'company_id' => 1, 'name' => 'Complaint reader', 'permissions' => '[]']);
        DB::table('roles')->insert(['id' => 3, 'company_id' => 2, 'name' => 'Other company', 'permissions' => '[]']);
        $result = $this->postJson('/api/web/v1/role/get', ['id' => 2])->assertOk()->assertJsonCount(19, 'call_center_privileges');
        $new = $result->json('permissions');
        $this->assertFalse($new[0]['allow']);
        $this->assertSame([], array_filter(array_column($new[0]['buttons'], 'allow')));
        $new[0]['allow'] = true;
        foreach ($new[0]['buttons'] as &$button) { $button['allow'] = $button['name'] === 'call-center-view-complaints'; } unset($button);
        $body = ['id' => 2, 'company_id' => 1, 'name' => 'Complaint reader', 'permissions' => $new];
        $this->postJson('/api/web/v1/role/update', $body)->assertStatus(201);
        $this->assertSame($new, $this->postJson('/api/web/v1/role/get', ['id' => 2])->assertOk()->json('permissions'));
        $this->postJson('/api/web/v1/role/get', ['id' => 3])->assertNotFound();
        $body['id'] = 3;
        $this->postJson('/api/web/v1/role/update', $body)->assertNotFound();
        foreach ($permissions as &$module) { if ($module['name'] === 'users') $module['allow'] = false; } unset($module);
        DB::table('roles')->where('id', 1)->update(['permissions' => json_encode($permissions)]); $user->unsetRelation('role');
        $body['id'] = 2;
        $this->postJson('/api/web/v1/role/update', $body)->assertForbidden();
    }

    public function test_call_center_is_independent_of_ticketing_and_migration_preserves_denials(): void
    {
        $user = $this->agent(); Sanctum::actingAs($user);
        $permissions = $user->role->permissions;
        foreach ($permissions as &$module) { if ($module['name'] === 'ticketing') $module['allow'] = false; } unset($module);
        DB::table('roles')->where('id', 1)->update(['permissions' => json_encode($permissions)]); $user->unsetRelation('role');
        $this->getJson(self::BASE . '/options')->assertOk()->assertJsonPath('data.permissions.create', true);
        $this->postJson(self::BASE . '/calls', ['category' => 'GI', 'shift' => 1])->assertCreated();
        $old = [['name' => 'ticketing', 'allow' => false, 'childs' => [
            ['name' => 'bookings', 'allow' => true, 'buttons' => [['name' => 'add-booking', 'allow' => true]]],
            ['name' => 'call-center', 'allow' => true, 'buttons' => [['name' => 'call-center-close-complaints', 'allow' => false]]],
        ]]];
        DB::table('roles')->where('id', 1)->update(['permissions' => json_encode($old)]);
        require_once database_path('migrations/2026_10_15_000006_separate_call_center_permissions.php');
        (new \SeparateCallCenterPermissions)->up();
        $moved = json_decode(DB::table('roles')->where('id', 1)->value('permissions'), true);
        $this->assertSame('call-center', $moved[0]['name']); $this->assertFalse($moved[0]['allow']);
        $this->assertSame([$old[0]['childs'][0]], $moved[1]['childs']); $this->assertFalse($moved[1]['allow']);
        $this->assertFalse(array_column($moved[0]['buttons'], 'allow', 'name')['call-center-close-complaints']);
        $user->unsetRelation('role'); $user->unsetRelation('company');
        $this->getJson(self::BASE . '/options')->assertForbidden();
        (new \SeparateCallCenterPermissions)->up();
        $this->assertSame($moved, json_decode(DB::table('roles')->where('id', 1)->value('permissions'), true));
        $old[0]['allow'] = true;
        $both = array_merge($moved, $old);
        $normalized = CallCenterPermissions::append($both, false, true);
        $this->assertFalse($normalized[0]['allow']); // Explicit standalone denial beats old nested grants.
        $this->assertSame(1, count(array_filter($normalized, function ($module) { return $module['name'] === 'call-center'; })));
    }
    public function test_refund_and_reschedule_calls_record_agreement_without_touching_ticketing(): void
    {
        $user = $this->agent(); Sanctum::actingAs($user); $this->ticket(100);
        $before = DB::table('tickets')->get()->toJson();
        $this->getJson(self::BASE . '/options')->assertOk()->assertJsonFragment(['value' => 'RF', 'label' => 'Refund'])
            ->assertJsonFragment(['value' => 'RS', 'label' => 'Reschedule'])->assertJsonCount(3, 'data.customer_responses');
        $refund = $this->postJson(self::BASE . '/calls', ['category' => 'RF', 'shift' => 1, 'customer_response' => 'agreed',
            'change_seat_count' => 2, 'proposed_refund_amount' => '4500.50', 'seat_count' => 99, 'ticket_id' => 100,
            'complaint_seat_number' => '12, 13', 'outcome' => 'record'])->assertCreated()->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.customer_response', 'agreed')->assertJsonPath('data.change_seat_count', 2)
            ->assertJsonPath('data.proposed_refund_amount', '4500.50')->assertJsonPath('data.seat_count', 0)->json('data.id');
        $reschedule = $this->postJson(self::BASE . '/calls', ['category' => 'RS', 'shift' => 1, 'customer_response' => 'agreed',
            'proposed_travel_date' => '2026-10-15', 'proposed_bus_time' => '18:30', 'outcome' => 'record'])->assertCreated()
            ->assertJsonPath('data.ticket_id', null)->assertJsonPath('data.change_seat_count', 1)->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.proposed_travel_date', '2026-10-15')->assertJsonPath('data.proposed_bus_time', '18:30')->json('data.id');
        $this->getJson(self::BASE . '/calls/' . $refund)->assertOk()->assertJsonPath('data.complaint_seat_number', '12, 13')->assertJsonPath('data.audit.0.actor.id', $user->id);
        $this->getJson(self::BASE . '/calls?category=RS')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.id', $reschedule);
        $this->assertSame($before, DB::table('tickets')->get()->toJson());
        $this->assertSame(0, DB::table('booking_cancels')->count()); $this->assertSame(0, DB::table('ticket_reschedules')->count());
    }

    public function test_customer_response_and_proposal_validation_are_enforced_by_the_api(): void
    {
        Sanctum::actingAs($this->agent());
        $base = ['category' => 'RF', 'shift' => 1, 'outcome' => 'record'];
        $this->postJson(self::BASE . '/calls', $base)->assertUnprocessable()->assertJsonValidationErrors('customer_response');
        foreach ([['customer_response' => 'paid'], ['customer_response' => 'agreed', 'change_seat_count' => 0],
            ['customer_response' => 'agreed', 'change_seat_count' => 101], ['customer_response' => 'agreed', 'change_seat_count' => 1.5],
            ['customer_response' => 'agreed', 'proposed_refund_amount' => -1], ['customer_response' => 'agreed', 'proposed_refund_amount' => '12.345'],
            ['customer_response' => 'agreed', 'proposed_refund_amount' => '10000000000']] as $fields) {
            $this->postJson(self::BASE . '/calls', $base + $fields)->assertUnprocessable();
        }
        $this->postJson(self::BASE . '/calls', ['category' => 'RS', 'shift' => 1, 'customer_response' => 'agreed', 'proposed_travel_date' => '2026-02-30'])->assertUnprocessable();
        $this->postJson(self::BASE . '/calls', ['category' => 'RS', 'shift' => 1, 'customer_response' => 'agreed', 'proposed_bus_time' => '25:00'])->assertUnprocessable();
        $this->assertSame(0, CallLog::count());
        $this->postJson(self::BASE . '/calls', $base + ['customer_response' => 'declined', 'proposed_refund_amount' => '0.00'])
            ->assertCreated()->assertJsonPath('data.status', 'closed')->assertJsonPath('data.proposed_refund_amount', '0.00');
    }

    public function test_unanswered_requests_remain_in_date_independent_scoped_queue(): void
    {
        $user = $this->agent(); $other = $this->agent(2); Sanctum::actingAs($user);
        $id = $this->postJson(self::BASE . '/calls', ['category' => 'RF', 'shift' => 1, 'customer_response' => 'not_reached',
            'call_date' => '2026-10-12', 'outcome' => 'record'])->assertCreated()->assertJsonPath('data.status', 'waiting')
            ->assertJsonPath('data.assigned_to', $user->id)->json('data.id');
        $this->logCall($user, ['category' => 'RS', 'customer_response' => 'agreed', 'status' => 'closed']);
        $this->logCall($other, ['category' => 'RS', 'customer_response' => 'not_reached']);
        $this->getJson(self::BASE . '/calls?queue=awaiting_agreement&date=2026-10-13')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.id', $id);
        $this->getJson(self::BASE . '/calls?queue=awaiting_agreement&terminal_id=11')->assertForbidden();
        $this->patchJson(self::BASE . '/calls/' . $id, ['customer_response' => 'agreed', 'outcome' => 'record'])->assertOk()->assertJsonPath('data.status', 'closed');
        $this->getJson(self::BASE . '/calls?queue=awaiting_agreement')->assertOk()->assertJsonPath('data.total', 0);
    }

    public function test_proposal_edits_are_audited_and_cleared_when_changing_call_type(): void
    {
        $user = $this->agent(); Sanctum::actingAs($user);
        $id = $this->postJson(self::BASE . '/calls', ['category' => 'RF', 'shift' => 1, 'customer_response' => 'agreed',
            'proposed_refund_amount' => '2500', 'change_seat_count' => 3])->assertCreated()->json('data.id');
        $this->patchJson(self::BASE . '/calls/' . $id, ['remarks' => 'Customer confirmed again'])->assertOk()->assertJsonPath('data.proposed_refund_amount', '2500.00');
        $this->patchJson(self::BASE . '/calls/' . $id, ['category' => 'RS'])->assertUnprocessable()->assertJsonValidationErrors('customer_response');
        $this->patchJson(self::BASE . '/calls/' . $id, ['category' => 'RS', 'customer_response' => 'declined', 'proposed_travel_date' => '2026-10-15', 'proposed_bus_time' => '16:00'])->assertOk()
            ->assertJsonPath('data.proposed_refund_amount', null)->assertJsonPath('data.customer_response', 'declined')->assertJsonPath('data.change_seat_count', 3);
        $this->getJson(self::BASE . '/calls/' . $id)->assertOk()->assertJsonPath('data.audit.2.changes.customer_response.to', 'declined');
        $this->patchJson(self::BASE . '/calls/' . $id, ['category' => 'GI'])->assertOk()->assertJsonPath('data.customer_response', null)
            ->assertJsonPath('data.change_seat_count', null)->assertJsonPath('data.proposed_travel_date', null)->assertJsonPath('data.proposed_bus_time', null);
    }

    public function test_refund_and_reschedule_calls_use_existing_action_and_booking_boundaries(): void
    {
        $user = $this->agent(); $other = $this->agent(2); Sanctum::actingAs($user);
        $this->ticket(100, ['terminal_id' => 11]); $this->ticket(101, ['company_id' => 2, 'terminal_id' => 20]);
        $this->grant($user, ['view-calls']);
        $base = ['category' => 'RF', 'shift' => 1, 'customer_response' => 'agreed'];
        $this->postJson(self::BASE . '/calls', $base)->assertForbidden();
        $this->grant($user, ['create-calls']);
        $id = $this->postJson(self::BASE . '/calls', $base)->assertCreated()->json('data.id');
        $this->postJson(self::BASE . '/calls', $base + ['ticket_id' => 100])->assertForbidden();
        $this->grant($user, ['create-calls', 'passenger-lookup']);
        foreach ([100, 101] as $ticket) { $this->postJson(self::BASE . '/calls', $base + ['ticket_id' => $ticket])->assertUnprocessable(); }
        $this->patchJson(self::BASE . '/calls/' . $id, ['customer_response' => 'declined'])->assertForbidden();
        $this->grant($user, ['view-calls', 'edit-calls']);
        $this->patchJson(self::BASE . '/calls/' . $id, ['customer_response' => 'declined'])->assertOk();
        $foreign = $this->logCall($other, ['category' => 'RS', 'customer_response' => 'agreed']);
        $this->getJson(self::BASE . '/calls/' . $foreign->id)->assertNotFound();
    }

    public function test_agreement_totals_are_separate_from_completed_ticket_changes_and_invalidate_reviews(): void
    {
        $supervisor = $this->agent(1, 1, 10, true); $agent = $this->agent(2);
        $this->ticket(100, ['type' => 'canceled', 'deleted_at' => now(), 'refund_amount' => 2000]);
        DB::table('booking_cancels')->insert(['company_id' => 1, 'ticket_id' => 100, 'added_by' => 2, 'type' => 'booked', 'created_at' => now()]);
        $this->logCall($agent, ['category' => 'RF', 'customer_response' => 'agreed', 'change_seat_count' => 3, 'status' => 'closed']);
        $this->logCall($agent, ['category' => 'RF', 'customer_response' => 'declined', 'change_seat_count' => 2, 'status' => 'closed']);
        $waiting = $this->logCall($agent, ['category' => 'RS', 'customer_response' => 'not_reached', 'change_seat_count' => 4]);
        $this->logCall($agent, ['category' => 'RS', 'customer_response' => 'agreed', 'change_seat_count' => 2, 'status' => 'closed']);
        Sanctum::actingAs($supervisor);
        foreach (['/summary', '/daily-report'] as $endpoint) {
            $this->getJson(self::BASE . $endpoint . '?date=2026-10-13')->assertOk()->assertJsonPath('data.totals.total', 4)
                ->assertJsonPath('data.totals.RF', 2)->assertJsonPath('data.totals.RS', 2)->assertJsonPath('data.totals.seats', 0)
                ->assertJsonPath('data.customer_agreements.refund.calls', 2)->assertJsonPath('data.customer_agreements.refund.agreed', 1)
                ->assertJsonPath('data.customer_agreements.refund.declined', 1)->assertJsonPath('data.customer_agreements.refund.agreed_seats', 3)
                ->assertJsonPath('data.customer_agreements.reschedule.not_reached', 1)->assertJsonPath('data.customer_agreements.reschedule.agreed_seats', 2);
        }
        $this->getJson(self::BASE . '/daily-report?date=2026-10-13')->assertOk()->assertJsonPath('data.seat_movements.0.refunded', 1);
        $this->postJson(self::BASE . '/daily-review', ['date' => '2026-10-13', 'snapshot_token' => $this->snapshot()])->assertOk();
        $this->patchJson(self::BASE . '/calls/' . $waiting->id, ['customer_response' => 'agreed', 'outcome' => 'record'])->assertOk();
        $this->getJson(self::BASE . '/daily-report?date=2026-10-13')->assertOk()->assertJsonPath('data.review.stale', true)
            ->assertJsonPath('data.customer_agreements.reschedule.agreed_seats', 6)->assertJsonPath('data.seat_movements.0.refunded', 1);
        Sanctum::actingAs($agent);
        $this->getJson(self::BASE . '/summary?date=2026-10-13&shift=2')->assertOk()->assertJsonPath('data.customer_agreements.refund.calls', 0);
    }

    public function test_followup_cannot_hide_an_unanswered_customer_agreement(): void
    {
        $user = $this->agent(); Sanctum::actingAs($user);
        $call = $this->logCall($user, ['category' => 'RF', 'customer_response' => 'not_reached', 'change_seat_count' => 1, 'status' => 'waiting']);
        $this->postJson(self::BASE . '/calls', ['category' => 'FU', 'shift' => 1, 'parent_call_id' => $call->id,
            'resolve_parent' => true, 'outcome' => 'finish', 'remarks' => 'Could not reach customer'])->assertUnprocessable()->assertJsonValidationErrors('resolve_parent');
        $this->assertSame(1, CallLog::count()); $this->assertSame('waiting', $call->fresh()->status);
        $this->patchJson(self::BASE . '/calls/' . $call->id, ['status' => 'closed'])->assertOk()->assertJsonPath('data.status', 'waiting');
    }


    public function test_new_agreement_followups_keep_call_history_and_only_latest_attempt_pending(): void
    {
        $user = $this->agent(); Sanctum::actingAs($user);
        $parent = $this->logCall($user, ['category' => 'RF', 'customer_response' => 'not_reached', 'change_seat_count' => 2,
            'status' => 'waiting', 'call_date' => '2026-10-12', 'called_at' => '2026-10-12 20:00:00']);
        $base = ['category' => 'RF', 'shift' => 1, 'parent_call_id' => $parent->id, 'customer_response' => 'not_reached', 'outcome' => 'record'];
        $this->grant($user, ['create-calls', 'view-calls', 'follow-up']);
        $this->postJson(self::BASE . '/calls', $base)->assertForbidden();
        $this->assertSame(1, CallLog::count());
        $this->grant($user, ['create-calls', 'view-calls', 'follow-up', 'edit-calls']);
        $next = $this->postJson(self::BASE . '/calls', $base)->assertCreated()->assertJsonPath('data.status', 'waiting')->json('data.id');
        $this->assertSame('closed', $parent->fresh()->status); $this->assertSame('not_reached', $parent->fresh()->customer_response);
        $this->getJson(self::BASE . '/calls?queue=awaiting_agreement')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.id', $next);
        $base['parent_call_id'] = $next; $base['customer_response'] = 'agreed'; $base['change_seat_count'] = 2;
        $agreed = $this->postJson(self::BASE . '/calls', $base)->assertCreated()->assertJsonPath('data.status', 'closed')->json('data.id');
        $this->getJson(self::BASE . '/calls?queue=awaiting_agreement')->assertOk()->assertJsonPath('data.total', 0);
        $this->patchJson(self::BASE . '/calls/' . $parent->id, ['remarks' => 'First attempt, later followed up'])->assertOk()->assertJsonPath('data.status', 'closed');
        $this->grant($user, ['shift-report', 'view-calls', 'view-audit']);
        $this->getJson(self::BASE . '/summary?date=2026-10-13')->assertOk()->assertJsonPath('data.totals.RF', 2)
            ->assertJsonPath('data.customer_agreements.refund.agreed', 1)->assertJsonPath('data.customer_agreements.refund.not_reached', 1);
        $audit = $this->getJson(self::BASE . '/calls/' . $next)->assertOk()->json('data.audit');
        $this->assertSame($agreed, $audit[1]['changes']['resolved_by_call']);
    }

    public function test_agreement_followups_inherit_booking_access_but_cannot_change_the_booking(): void
    {
        $user = $this->agent(); Sanctum::actingAs($user); $this->ticket(100); $this->ticket(101);
        $parent = $this->logCall($user, ['category' => 'RF', 'customer_response' => 'not_reached', 'customer_id' => 100, 'ticket_id' => 100, 'status' => 'waiting']);
        $payload = ['category' => 'RF', 'shift' => 1, 'customer_response' => 'agreed', 'parent_call_id' => $parent->id,
            'customer_id' => 100, 'ticket_id' => 100, 'outcome' => 'record'];
        $this->postJson(self::BASE . '/calls', array_merge($payload, ['ticket_id' => 101, 'customer_id' => 101]))->assertUnprocessable()->assertJsonValidationErrors('ticket_id');
        $this->postJson(self::BASE . '/calls', array_merge($payload, ['category' => 'RS']))->assertUnprocessable()->assertJsonValidationErrors('parent_call_id');
        $this->grant($user, ['create-calls', 'view-calls', 'edit-calls', 'follow-up']);
        $this->postJson(self::BASE . '/calls', $payload)->assertCreated()->assertJsonPath('data.ticket_id', 100);
        $this->assertSame('closed', $parent->fresh()->status);
    }
}
