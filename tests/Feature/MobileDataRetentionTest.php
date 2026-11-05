<?php

namespace Tests\Feature;

use App\Models\PassengerAccount;
use App\Services\Mobile\MobilePersonalDataRetention;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class MobileDataRetentionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'retention_test',
            'database.connections.retention_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'cache.default' => 'array', 'mobile.company_id' => 1,
            'mobile.retention' => ['enabled' => true, 'notifications_days' => 90, 'login_events_days' => 90,
                'push_devices_days' => 90, 'deletion_audit_days' => 365, 'backup_max_days' => null, 'batch_size' => 2]]);
        $this->travelTo(Carbon::parse('2026-11-06 12:00:00', 'UTC'));
        Schema::create('passenger_accounts', function (Blueprint $t) {
            $t->id(); $t->integer('company_id'); $t->dateTime('first_mobile_login_at')->nullable();
            foreach (['otp', 'password_reset_otp', 'password_reset_token'] as $prefix) {
                $t->string($prefix.'_digest')->nullable(); $t->timestamp($prefix.'_expires_at')->nullable();
            }
            $t->string('password')->default('preserved-password'); $t->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id(); $t->morphs('tokenable'); $t->string('name'); $t->string('token', 64)->unique();
            $t->text('abilities')->nullable(); $t->timestamp('last_used_at')->nullable(); $t->timestamps();
        });
        Schema::create('pending_passenger_registrations', function (Blueprint $t) {
            $t->id(); $t->integer('company_id'); $t->string('otp_digest')->nullable();
            $t->timestamp('otp_expires_at')->nullable(); $t->integer('otp_attempts'); $t->timestamp('expires_at');
        });
        Schema::create('mobile_app_passenger_controls', function (Blueprint $t) {
            $t->id(); $t->integer('company_id'); $t->integer('passenger_account_id'); $t->integer('otp_daily_used');
        });
        require_once database_path('migrations/2026_10_20_000008_create_mobile_notifications.php');
        (new \CreateMobileNotifications)->up();
        require_once database_path('migrations/2026_10_20_000006_create_mobile_app_monitoring.php');
        (new \CreateMobileAppMonitoring)->up();
        require_once database_path('migrations/2026_09_15_000010_create_mobile_account_deletions_table.php');
        (new \CreateMobileAccountDeletionsTable)->up();
        DB::table('passenger_accounts')->insert(['id' => 1, 'company_id' => 1, 'first_mobile_login_at' => now('UTC')->subDays(120)]);
        DB::table('passenger_accounts')->insert(['id' => 2, 'company_id' => 2, 'first_mobile_login_at' => now('UTC')->subDays(120)]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        DB::purge('retention_test');
        parent::tearDown();
    }

    public function test_dry_run_is_default_and_execution_requires_explicit_enablement(): void
    {
        $id = $this->notice(1, 1, now()->subDays(100));
        config(['mobile.retention.enabled' => false]);
        $this->artisan('mobile:prune-personal-data')->expectsOutput('Dry run: no data changed. Counts are eligible items per stage and may overlap.')->assertExitCode(0);
        $this->assertDatabaseHas('mobile_notifications', ['id' => $id]);
        $this->artisan('mobile:prune-personal-data', ['--execute' => true])->assertExitCode(1);
        $this->artisan('mobile:prune-personal-data', ['--execute' => true, '--dry-run' => true])->assertExitCode(1);
        $this->assertDatabaseHas('mobile_notifications', ['id' => $id]);
    }

    public function test_prunes_expired_inbox_and_delivery_metadata_across_batches_with_strict_cutoffs_and_company_isolation(): void
    {
        $device = $this->device(1, 1, now());
        $old = [];
        for ($i = 0; $i < 5; $i++) {
            $old[] = $id = $this->notice(1, 1, now()->subDays(91));
            $this->delivery($id, $device, 'accepted');
        }
        $boundary = $this->notice(1, 1, now()->subDays(90));
        $recent = $this->notice(1, 1, now());
        $foreign = $this->notice(2, 2, now()->subDays(100));
        DB::table('mobile_notification_campaigns')->insert(['company_id' => 1, 'actor_id' => 1,
            'request_key' => 'preserved-idempotency', 'payload_hash' => str_repeat('a', 64), 'title' => 'Campaign', 'body' => 'Body',
            'audience' => '{}', 'reason' => 'Service update', 'recipient_count' => 5, 'created_at' => now()->subDays(100)]);
        DB::table('mobile_notification_booking_states')->insert(['invoice_id' => 7, 'company_id' => 1,
            'snapshot' => '{}', 'sequence' => 2, 'updated_at' => now()->subDays(100)]);
        $result = app(MobilePersonalDataRetention::class)->run(true);
        $this->assertSame(5, $result['counts']['notifications']);
        $this->assertSame(0, DB::table('mobile_notifications')->whereIn('id', $old)->count());
        $this->assertSame(0, DB::table('mobile_notification_deliveries')->count());
        $this->assertSame(3, DB::table('mobile_notifications')->whereIn('id', [$boundary, $recent, $foreign])->count());
        $this->assertSame(1, DB::table('mobile_notification_campaigns')->count());
        $this->assertSame(1, DB::table('mobile_notification_booking_states')->count());
    }

    public function test_processing_leases_survive_until_expired_for_old_notifications_and_devices(): void
    {
        $device = $this->device(1, 1, now()->subDays(100));
        $notice = $this->notice(1, 1, now()->subDays(100));
        $delivery = $this->delivery($notice, $device, 'processing', now()->addMinutes(5));
        app(MobilePersonalDataRetention::class)->run(true);
        $this->assertDatabaseHas('mobile_notifications', ['id' => $notice]);
        $this->assertDatabaseHas('mobile_push_devices', ['id' => $device]);
        $this->assertDatabaseHas('mobile_notification_deliveries', ['id' => $delivery]);
        $this->travel(6)->minutes();
        app(MobilePersonalDataRetention::class)->run(true);
        $this->assertDatabaseMissing('mobile_notifications', ['id' => $notice]);
        $this->assertDatabaseMissing('mobile_push_devices', ['id' => $device]);
        $this->assertDatabaseMissing('mobile_notification_deliveries', ['id' => $delivery]);
    }

    public function test_stale_device_cleanup_preserves_inbox_and_active_authentication(): void
    {
        $old = $this->device(1, 1, now()->subDays(91));
        $boundary = $this->device(1, 1, now()->subDays(90));
        $foreign = $this->device(2, 2, now()->subDays(100));
        $notice = $this->notice(1, 1, now());
        $delivery = $this->delivery($notice, $old, 'retry', now());
        $account = PassengerAccount::find(1);
        $token = $account->createToken('passenger-mobile');
        app(MobilePersonalDataRetention::class)->run(true);
        $this->assertDatabaseMissing('mobile_push_devices', ['id' => $old]);
        $this->assertDatabaseMissing('mobile_notification_deliveries', ['id' => $delivery]);
        $this->assertSame(2, DB::table('mobile_push_devices')->whereIn('id', [$boundary, $foreign])->count());
        $this->assertDatabaseHas('mobile_notifications', ['id' => $notice]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_login_retention_preserves_first_login_marker_and_unmigrated_history(): void
    {
        $old = $this->event(1, 1, now('UTC')->subDays(91));
        $boundary = $this->event(1, 1, now('UTC')->subDays(90));
        $foreign = $this->event(2, 2, now('UTC')->subDays(100));
        DB::table('passenger_accounts')->insert(['id' => 3, 'company_id' => 1]);
        $unmarked = $this->event(1, 3, now('UTC')->subDays(100));
        $first = DB::table('passenger_accounts')->where('id', 1)->value('first_mobile_login_at');
        app(MobilePersonalDataRetention::class)->run(true);
        $this->assertDatabaseMissing('mobile_app_login_events', ['id' => $old]);
        $this->assertSame(3, DB::table('mobile_app_login_events')->whereIn('id', [$boundary, $foreign, $unmarked])->count());
        $this->assertSame($first, DB::table('passenger_accounts')->where('id', 1)->value('first_mobile_login_at'));

        // Simulate pre-migration schema without relying on SQLite DROP COLUMN.
        Schema::drop('passenger_accounts');
        Schema::create('passenger_accounts', function (Blueprint $t) { $t->id(); $t->integer('company_id'); });
        $result = app(MobilePersonalDataRetention::class)->run(true);
        $this->assertContains('Login history expiry is paused until the first_mobile_login_at migration is installed.', $result['skipped']);
        $this->assertSame(3, DB::table('mobile_app_login_events')->count());
    }

    public function test_legacy_deleted_account_cleanup_requires_audit_and_absent_global_account(): void
    {
        $deleted = $this->notice(1, 10, now());
        $unknown = $this->notice(1, 11, now());
        $live = $this->notice(1, 1, now());
        $foreign = $this->notice(2, 20, now());
        $this->audit(1, 10, now()->subDays(400));
        $this->audit(1, 1, now()->subDays(400));
        $this->audit(2, 20, now()->subDays(400));
        // Inconsistent company marker must not touch another live account's token.
        $this->audit(1, 2, now()->subDays(400));
        $token = PassengerAccount::find(2)->createToken('passenger-mobile');
        $preview = app(MobilePersonalDataRetention::class)->run();
        $this->assertSame(1, $preview['counts']['deleted_accounts_checked']);
        $this->assertDatabaseHas('mobile_notifications', ['id' => $deleted]);
        app(MobilePersonalDataRetention::class)->run(true);
        $this->assertDatabaseMissing('mobile_notifications', ['id' => $deleted]);
        $this->assertSame(3, DB::table('mobile_notifications')->whereIn('id', [$unknown, $live, $foreign])->count());
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertSame(4, DB::table('mobile_account_deletions')->count());
    }

    public function test_audit_expiry_requires_confirmed_backup_horizon_and_occurs_after_private_cleanup(): void
    {
        $this->audit(1, 10, now()->subDays(366));
        $this->audit(1, 11, now()->subDays(365));
        $notice = $this->notice(1, 10, now());
        config(['mobile.retention.backup_max_days' => 35]);
        app(MobilePersonalDataRetention::class)->run(true);
        $this->assertDatabaseMissing('mobile_notifications', ['id' => $notice]);
        $this->assertDatabaseMissing('mobile_account_deletions', ['passenger_account_id' => 10]);
        $this->assertDatabaseHas('mobile_account_deletions', ['passenger_account_id' => 11]);
    }

    public function test_expired_credentials_are_cleared_without_resetting_attempts_budgets_or_active_credentials(): void
    {
        DB::table('passenger_accounts')->where('id', 1)->update([
            'otp_digest' => 'old-code', 'otp_expires_at' => now()->subMinute(),
            'password_reset_otp_digest' => 'active-code', 'password_reset_otp_expires_at' => now()->addMinute(),
            'password_reset_token_digest' => 'old-reset', 'password_reset_token_expires_at' => now()->subMinute(),
        ]);
        DB::table('passenger_accounts')->where('id', 2)->update(['otp_digest' => 'foreign-code', 'otp_expires_at' => now()->subDay()]);
        DB::table('mobile_app_passenger_controls')->insert(['company_id' => 1, 'passenger_account_id' => 1, 'otp_daily_used' => 7]);
        foreach ([1, 2] as $company) DB::table('pending_passenger_registrations')->insert([
            'company_id' => $company, 'otp_digest' => 'pending-expired', 'otp_expires_at' => now()->subMinute(),
            'otp_attempts' => 4, 'expires_at' => now()->addHours(23)]);
        $preview = app(MobilePersonalDataRetention::class)->run();
        $this->assertSame(2, $preview['counts']['expired_account_credentials']);
        $this->assertDatabaseHas('passenger_accounts', ['id' => 1, 'otp_digest' => 'old-code']);
        app(MobilePersonalDataRetention::class)->run(true);
        $this->assertDatabaseHas('passenger_accounts', ['id' => 1, 'otp_digest' => null, 'otp_expires_at' => null,
            'password_reset_token_digest' => null, 'password_reset_token_expires_at' => null,
            'password_reset_otp_digest' => 'active-code', 'password' => 'preserved-password']);
        $this->assertDatabaseHas('passenger_accounts', ['id' => 2, 'otp_digest' => 'foreign-code']);
        $this->assertDatabaseHas('pending_passenger_registrations', ['company_id' => 1, 'otp_digest' => null, 'otp_attempts' => 4]);
        $this->assertDatabaseHas('pending_passenger_registrations', ['company_id' => 2, 'otp_digest' => 'pending-expired', 'otp_attempts' => 4]);
        $this->assertDatabaseHas('mobile_app_passenger_controls', ['otp_daily_used' => 7]);
    }

    /** @dataProvider invalidSettings */
    public function test_invalid_retention_configuration_fails_before_mutation(string $key, $value): void
    {
        $notice = $this->notice(1, 1, now()->subDays(100));
        config([$key => $value]);
        try {
            app(MobilePersonalDataRetention::class)->run(true);
            $this->fail('Invalid retention configuration was accepted.');
        } catch (InvalidArgumentException $exception) {
            $this->assertDatabaseHas('mobile_notifications', ['id' => $notice]);
        }
    }

    public static function invalidSettings(): array
    {
        return [['mobile.company_id', 0], ['mobile.retention.notifications_days', -1],
            ['mobile.retention.login_events_days', 'abc'], ['mobile.retention.push_devices_days', 0],
            ['mobile.retention.batch_size', 1001], ['mobile.retention.deletion_audit_days', true],
            ['mobile.retention.backup_max_days', 365]];
    }

    private function notice(int $company, int $account, Carbon $when): int
    {
        return DB::table('mobile_notifications')->insertGetId(['company_id' => $company, 'passenger_account_id' => $account,
            'event_key' => 'event-'.bin2hex(random_bytes(8)), 'kind' => 'announcement', 'title' => 'Private title',
            'body' => 'Private body', 'created_at' => $when]);
    }

    private function device(int $company, int $account, Carbon $when): int
    {
        return DB::table('mobile_push_devices')->insertGetId(['company_id' => $company, 'passenger_account_id' => $account,
            'access_token_id' => 1, 'token_hash' => bin2hex(random_bytes(32)), 'token' => 'private-token',
            'platform' => 'android', 'created_at' => $when, 'updated_at' => $when]);
    }

    private function delivery(int $notice, int $device, string $status, ?Carbon $next = null): int
    {
        return DB::table('mobile_notification_deliveries')->insertGetId(['notification_id' => $notice,
            'device_id' => $device, 'status' => $status, 'next_attempt_at' => $next]);
    }

    private function event(int $company, int $account, Carbon $when): int
    {
        return DB::table('mobile_app_login_events')->insertGetId(['company_id' => $company, 'passenger_account_id' => $account,
            'kind' => 'password', 'first_login' => false, 'occurred_at' => $when]);
    }

    private function audit(int $company, int $account, Carbon $when): void
    {
        DB::table('mobile_account_deletions')->insert(['company_id' => $company, 'passenger_account_id' => $account, 'deleted_at' => $when]);
    }
}
