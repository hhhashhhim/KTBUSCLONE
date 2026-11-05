<?php

namespace Tests\Feature;

use App\Models\PassengerAccount;
use App\Services\Mobile\MobileAppTelemetry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileRetentionTelemetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'retention_telemetry_test',
            'database.connections.retention_telemetry_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'mobile.company_id' => 1]);
        Schema::create('passenger_accounts', function (Blueprint $table) {
            $table->id(); $table->integer('company_id'); $table->timestamps();
        });
        require_once database_path('migrations/2026_10_20_000006_create_mobile_app_monitoring.php');
        (new \CreateMobileAppMonitoring)->up();
        require_once database_path('migrations/2026_11_06_000001_preserve_mobile_first_login.php');
    }

    protected function tearDown(): void
    {
        DB::purge('retention_telemetry_test');
        parent::tearDown();
    }

    public function test_migration_backfills_only_matching_company_and_resumes_without_overwriting_marker(): void
    {
        $account = PassengerAccount::create(['company_id' => 1]);
        $empty = PassengerAccount::create(['company_id' => 1]);
        foreach ([[1, '2026-01-02 00:00:00'], [1, '2026-01-03 00:00:00'], [2, '2025-01-01 00:00:00']] as [$company, $time]) {
            DB::table('mobile_app_login_events')->insert(['company_id' => $company, 'passenger_account_id' => $account->id,
                'kind' => 'password', 'first_login' => true, 'occurred_at' => $time]);
        }
        (new \PreserveMobileFirstLogin)->up();
        $this->assertSame('2026-01-02 00:00:00', $account->fresh()->first_mobile_login_at->format('Y-m-d H:i:s'));
        $this->assertNull($empty->fresh()->first_mobile_login_at);
        DB::table('mobile_app_login_events')->delete();
        (new \PreserveMobileFirstLogin)->up();
        (new MobileAppTelemetry)->recordLogin($account, 'password');
        $this->assertSame(0, (int) DB::table('mobile_app_login_events')->value('first_login'));
        $this->assertArrayNotHasKey('first_mobile_login_at', $account->fresh()->toArray());
    }

    public function test_new_login_marker_survives_history_expiry_and_rejects_deleted_accounts(): void
    {
        (new \PreserveMobileFirstLogin)->up();
        $account = PassengerAccount::create(['company_id' => 1]);
        $telemetry = new MobileAppTelemetry;
        $telemetry->recordLogin($account, 'password');
        $this->assertSame(1, (int) DB::table('mobile_app_login_events')->value('first_login'));
        DB::table('mobile_app_login_events')->delete();
        $telemetry->recordLogin($account, 'biometric');
        $this->assertSame(0, (int) DB::table('mobile_app_login_events')->value('first_login'));
        $account->delete();
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $telemetry->recordLogin($account, 'password');
    }
}
