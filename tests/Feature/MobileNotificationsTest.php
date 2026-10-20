<?php
namespace Tests\Feature;

use App\Models\PassengerAccount;
use App\Services\Mobile\MobileNotificationInbox as Inbox;
use App\Services\Mobile\MobileNotificationDelivery;
use App\Services\Mobile\MobileBookingNotifications;
use App\Services\Mobile\MobilePushGateway;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class MobileNotificationsTest extends TestCase
{
    private $account; private $token;
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'notice_test', 'database.connections.notice_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'cache.default' => 'array', 'mobile.company_id' => 1, 'mobile_notifications.enabled' => true, 'sanctum.expiration' => 600]);
        Schema::create('passenger_accounts', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->string('mobile'); $t->timestamp('mobile_verified_at')->nullable(); $t->timestamps(); });
        Schema::create('personal_access_tokens', function (Blueprint $t) { $t->id(); $t->morphs('tokenable'); $t->string('name'); $t->string('token',64)->unique(); $t->text('abilities')->nullable(); $t->timestamp('last_used_at')->nullable(); $t->timestamps(); });
        Schema::create('invoices', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->integer('passenger_account_id'); $t->timestamps(); });
        Schema::create('tickets', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->integer('invoice_id'); $t->string('type'); $t->string('seat_no'); $t->date('schedule_date'); $t->string('schedule_time'); $t->decimal('refund_amount')->nullable(); $t->softDeletes(); });
        Schema::create('mobile_payments', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->integer('passenger_account_id'); $t->integer('invoice_id'); $t->string('environment')->default('live'); $t->timestamp('paid_at')->nullable(); });
        require_once database_path('migrations/2026_10_20_000008_create_mobile_notifications.php'); (new \CreateMobileNotifications)->up();
        $this->account = PassengerAccount::create(['company_id' => 1, 'mobile' => '03001234567', 'mobile_verified_at' => now()]);
        $this->token = $this->account->createToken('passenger-mobile')->plainTextToken;
    }
    protected function tearDown(): void { $this->travelBack(); DB::purge('notice_test'); parent::tearDown(); }
    private function request(string $method, string $path, array $data = [], ?string $token = null) {
        $this->app['auth']->forgetGuards();
        return $this->json($method, '/api/mobile/v1/' . $path, $data, ['Authorization' => 'Bearer ' . ($token ?? $this->token)]);
    }
    private function notice(int $account = 1, int $company = 1, string $key = 'test'): int {
        Inbox::record($company, $account, $key, 'announcement', 'An update', 'Private passenger message');
        return DB::table('mobile_notifications')->where('event_key', $key)->where('company_id', $company)->where('passenger_account_id', $account)->value('id');
    }
    private function register(string $fid = 'cExampleFirebaseInstallation1'): int {
        $this->request('PUT', 'notifications/device', ['token' => $fid, 'platform' => 'android'])->assertOk();
        return DB::table('mobile_push_devices')->where('token_hash', hash('sha256', $fid))->value('id');
    }
    private function gateway(string $result = 'accepted', int $times = 1) {
        $g = Mockery::mock(MobilePushGateway::class); $g->shouldReceive('configured')->andReturn(true);
        $g->shouldReceive('send')->times($times)->andReturn($result); return $g;
    }
    public function test_inbox_is_paginated_and_private_to_account_and_company() {
        $this->notice(); $this->notice(2, 1); $this->notice(1, 2);
        $this->request('GET', 'notifications?paginated=1')->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('data.unread_count', 1)->assertJsonPath('data.pagination.total', 1);
        $this->request('GET', 'notifications')->assertOk()->assertJsonCount(1, 'data');
        $this->request('GET', 'notifications?per_page=1000')->assertUnprocessable();
    }
    public function test_unauthenticated_wrong_company_and_staff_cannot_read_passenger_messages() {
        $this->notice();
        $this->request('GET', 'notifications', [], 'invalid')->assertUnauthorized();
        config(['mobile.company_id' => 2]); $this->request('GET', 'notifications')->assertForbidden();
        config(['mobile.company_id' => 1]);
        $this->actingAs(new \Illuminate\Auth\GenericUser(['id' => 1, 'company_id' => 1]), 'sanctum');
        $this->getJson('/api/mobile/v1/notifications')->assertForbidden();
    }
    public function test_read_endpoints_do_not_allow_idor_or_mark_newer_messages_as_read() {
        $one = $this->notice(); $two = $this->notice(1,1,'newer'); $other = $this->notice(2,1,'private');
        $this->request('GET', 'notifications/' . $other)->assertNotFound();
        $this->request('POST', 'notifications/' . $other . '/read')->assertNotFound();
        $this->request('POST', 'notifications/read-all', ['through_id' => $one])->assertOk()->assertJsonPath('data.unread_count', 1);
        $this->request('POST', 'notifications/' . $two . '/read')->assertOk()->assertJsonPath('data.unread_count', 0);
        $when = DB::table('mobile_notifications')->where('id',$two)->value('read_at');
        $this->travel(5)->minutes(); $this->request('POST','notifications/'.$two.'/read')->assertOk();
        $this->assertSame($when, DB::table('mobile_notifications')->where('id',$two)->value('read_at'));
        $this->assertNull(DB::table('mobile_notifications')->where('id',$other)->value('read_at'));
    }
    public function test_device_registration_is_encrypted_idempotent_and_bound_to_access_token() {
        $id = $this->register(); $this->register();
        $this->assertSame(1, DB::table('mobile_push_devices')->count());
        $device = DB::table('mobile_push_devices')->first();
        $this->assertNotSame('cExampleFirebaseInstallation1', $device->token);
        $this->assertSame('cExampleFirebaseInstallation1', Crypt::decryptString($device->token));
        $this->assertEquals(explode('|',$this->token)[0], $device->access_token_id);
        $this->request('DELETE','notifications/device',['token'=>'cExampleFirebaseInstallation1'])->assertOk();
        $this->assertSame(0,DB::table('mobile_push_devices')->count());
    }
    public function test_push_delivery_is_deduplicated_and_reports_provider_acceptance() {
        $this->register(); $this->notice(); $service = new MobileNotificationDelivery; $g = $this->gateway();
        $service->run($g); $service->run($g);
        $this->assertSame('accepted', DB::table('mobile_notification_deliveries')->value('status'));
        $this->assertSame(1, DB::table('mobile_notification_deliveries')->count());
    }
    public function test_revoked_and_expired_sessions_do_not_receive_push() {
        $this->register(); $this->notice(); $this->account->tokens()->update(['created_at'=>now()->subHours(11)]);
        (new MobileNotificationDelivery)->run($this->gateway('accepted',0));
        $this->assertSame('skipped',DB::table('mobile_notification_deliveries')->value('status'));
        $this->account->tokens()->delete(); $this->notice(1,1,'after-revoke');
        (new MobileNotificationDelivery)->run($this->gateway('accepted',0));
        $this->assertSame(2, DB::table('mobile_notification_deliveries')->where('status','skipped')->count());
    }
    public function test_read_or_stale_notifications_do_not_create_deliveries() {
        $this->register(); $id=$this->notice(); $old=$this->notice(1,1,'old');
        DB::table('mobile_notifications')->where('id',$id)->update(['read_at'=>now()]);
        DB::table('mobile_notifications')->where('id',$old)->update(['created_at'=>now()->subDays(2)]);
        (new MobileNotificationDelivery)->run($this->gateway('accepted',0));
        $this->assertSame(0,DB::table('mobile_notification_deliveries')->count());
    }
    public function test_unregistered_device_is_removed_and_transient_failure_retries_with_limit() {
        $this->register(); $this->notice(); $service=new MobileNotificationDelivery;
        $service->run($this->gateway('unregistered'));
        $this->assertSame(0,DB::table('mobile_push_devices')->count());
        $this->register('cAnotherFirebaseInstallation2'); $this->notice(1,1,'retry');
        for($i=0;$i<5;$i++) { $service->run($this->gateway('retry')); $this->travel(40)->minutes(); }
        $this->assertSame(2,DB::table('mobile_notification_deliveries')->where('status','failed')->count());
    }
    private function invoice(string $type='advance booking', bool $old=false) {
        $id=DB::table('invoices')->insertGetId(['company_id'=>1,'passenger_account_id'=>1,'created_at'=>$old?now()->subDays(3):now(),'updated_at'=>now()]);
        DB::table('tickets')->insert(['company_id'=>1,'invoice_id'=>$id,'type'=>$type,'seat_no'=>'5','schedule_date'=>now('Asia/Karachi')->addDay()->toDateString(),'schedule_time'=>'15:00:00']);
        return DB::table('invoices')->where('id',$id)->first();
    }
    public function test_automatic_booking_changes_do_not_replay_history_or_duplicate_alerts() {
        $s=new MobileBookingNotifications; $invoice=$this->invoice('advance booking',true); $start=now()->toDateTimeString();
        $s->sync($invoice,$start); $s->sync($invoice,$start); $this->assertSame(0,DB::table('mobile_notifications')->count());
        DB::table('tickets')->where('invoice_id',$invoice->id)->update(['type'=>'booked']);
        $s->sync($invoice,$start); $s->sync($invoice,$start);
        $this->assertSame(2,DB::table('mobile_notifications')->count());
        $this->assertSame(1,DB::table('mobile_notifications')->where('kind','booking_confirmed')->count());
        DB::table('tickets')->where('invoice_id',$invoice->id)->update(['deleted_at'=>now(),'refund_amount'=>100]);
        $s->sync($invoice,$start); $s->sync($invoice,$start);
        $this->assertSame(1,DB::table('mobile_notifications')->where('kind','refund_updated')->count());
        $this->assertSame(1,DB::table('mobile_notifications')->where('kind','booking_cancelled')->count());
    }
    public function test_new_bookings_and_departure_reminders_are_recorded_once() {
        $s=new MobileBookingNotifications; $invoice=$this->invoice('booked'); $start=now()->subMinute()->toDateTimeString();
        $departure=now('Asia/Karachi')->addHour();
        DB::table('tickets')->where('invoice_id',$invoice->id)->update(['schedule_date'=>$departure->toDateString(),'schedule_time'=>$departure->format('H:i:s')]);
        $s->sync($invoice,$start); $s->sync($invoice,$start);
        $this->assertSame(1,DB::table('mobile_notifications')->where('kind','departure_reminder')->count());
        $this->assertSame(2,DB::table('mobile_notifications')->count());
    }
    public function test_sandbox_bookings_never_send_customer_alerts() {
        $invoice=$this->invoice(); DB::table('mobile_payments')->insert(['company_id'=>1,'passenger_account_id'=>1,'invoice_id'=>$invoice->id,'environment'=>'sandbox']);
        (new MobileBookingNotifications)->sync($invoice,now()->subHour()->toDateTimeString());
        $this->assertSame(0,DB::table('mobile_notifications')->count());
    }
    public function test_disabled_feature_and_schema_guard_do_not_advertise_notifications() {
        config(['mobile_notifications.enabled'=>false]); $this->request('GET','notifications?paginated=1')->assertStatus(503);
        $this->assertFalse(Inbox::enabled());
        config(['mobile_notifications.enabled'=>true]); Schema::drop('mobile_notifications'); $this->assertFalse(Inbox::enabled());
    }
    public function test_company_pause_disables_inbox_and_can_be_reenabled() {
        Schema::create('mobile_app_configs', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->json('features')->nullable(); });
        DB::table('mobile_app_configs')->insert(['company_id'=>1,'features'=>json_encode(['notifications'=>false])]);
        $this->request('GET','notifications?paginated=1')->assertStatus(503);
        DB::table('mobile_app_configs')->update(['features'=>json_encode(['notifications'=>true])]);
        $this->request('GET','notifications?paginated=1')->assertOk();
    }
    public function test_fcm_http_contract_uses_fid_and_generic_lock_screen_content() {
        $key=openssl_pkey_new(['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key,$pem); $file=tempnam(sys_get_temp_dir(),'kt-fcm-test-');
        file_put_contents($file,json_encode(['project_id'=>'test-project','client_email'=>'test@example.test','private_key'=>$pem]));
        config(['mobile_notifications.credentials_file'=>$file,'mobile_notifications.project_id'=>'test-project','mobile_notifications.push_enabled'=>true]);
        \Illuminate\Support\Facades\Http::fake([
            'oauth2.googleapis.com/*'=>\Illuminate\Support\Facades\Http::response(['access_token'=>'test-oauth-token'],200),
            'fcm.googleapis.com/*'=>\Illuminate\Support\Facades\Http::response(['name'=>'projects/test-project/messages/123'],200),
        ]);
        try {
            $this->assertSame('accepted',(new MobilePushGateway)->send('test-firebase-installation',42));
            \Illuminate\Support\Facades\Http::assertSent(function($request) {
                if(strpos($request->url(),'fcm.googleapis.com')===false)return false;
                $this->assertSame('test-firebase-installation',$request['message']['fid']);
                $this->assertSame(['notification_id'=>'42'],$request['message']['data']);
                $this->assertSame('Kainat Travels',$request['message']['notification']['title']);
                $this->assertStringNotContainsString('passenger_account_id',json_encode($request->data()));
                return true;
            });
        } finally { unlink($file); }
    }

    public function test_rescheduled_history_does_not_change_current_seats_or_confirmation() {
        $invoice=$this->invoice('booked',true); $scanner=new MobileBookingNotifications;
        $scanner->sync($invoice,now()->toDateTimeString());
        DB::table('tickets')->insert(['company_id'=>1,'invoice_id'=>$invoice->id,'type'=>'reschedule','seat_no'=>'9','schedule_date'=>now()->toDateString(),'schedule_time'=>'08:00:00']);
        $scanner->sync($invoice,now()->toDateTimeString());
        $this->assertSame(0,DB::table('mobile_notifications')->count());
        $snapshot=json_decode(DB::table('mobile_notification_booking_states')->value('snapshot'),true);
        $this->assertSame('confirmed',$snapshot['status']); $this->assertSame(['5'],$snapshot['seats']);
    }
    public function test_already_cancelled_new_bookings_do_not_send_a_booking_received_alert() {
        $invoice=$this->invoice('canceled');
        (new MobileBookingNotifications)->sync($invoice,now()->subMinute()->toDateTimeString());
        $this->assertSame(0,DB::table('mobile_notifications')->count());
    }
    public function test_recent_bookings_are_processed_even_with_a_history_backlog() {
        for($i=0;$i<501;$i++) $this->invoice('advance booking',true);
        DB::table('mobile_notification_runtime')->insert(['company_id'=>1,'started_at'=>now()->subMinute()]);
        $new=$this->invoice(); (new MobileBookingNotifications)->run();
        $this->assertSame(1,DB::table('mobile_notifications')->where('invoice_id',$new->id)->where('kind','booking_created')->count());
    }
    public function test_notification_migration_resumes_and_uses_legacy_mysql_compatible_dates() {
        $id=$this->notice(); Schema::drop('mobile_notification_runtime'); (new \CreateMobileNotifications)->up();
        $this->assertTrue(Schema::hasTable('mobile_notification_runtime'));
        $this->assertTrue(DB::table('mobile_notifications')->where('id',$id)->exists());
        config(['database.connections.notice_mysql'=>['driver'=>'mysql','database'=>'unused','prefix'=>'','charset'=>'utf8mb4','collation'=>'utf8mb4_unicode_ci'],'database.default'=>'notice_mysql']);
        try {
            $queries=DB::pretend(function(){ (new \CreateMobileNotifications)->up(); });
            $sql=collect($queries)->pluck('query')->first(function($q){return strpos($q,'create table `mobile_notifications`')===0;});
            $this->assertStringContainsString('`created_at` datetime not null',$sql);
            $this->assertStringNotContainsString('`created_at` timestamp',$sql);
        } finally { config(['database.default'=>'notice_test']); DB::purge('notice_mysql'); }
    }

}
