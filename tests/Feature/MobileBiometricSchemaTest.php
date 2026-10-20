<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileBiometricSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.biometric_schema_testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
        config()->set('database.default', 'biometric_schema_testing');
        require_once database_path('migrations/2026_10_20_000007_create_mobile_biometric_sessions.php');
    }

    protected function tearDown(): void
    {
        DB::purge('biometric_schema_testing');
        DB::purge('biometric_schema_mysql');
        parent::tearDown();
    }

    public function test_partial_migration_can_resume_without_losing_existing_sessions()
    {
        $migration = new \CreateMobileBiometricSessions();
        $migration->up();
        DB::table('mobile_biometric_sessions')->insert([
            'company_id' => 1, 'passenger_account_id' => 2, 'access_token_id' => 3,
            'expires_at' => '2026-11-20 12:00:00',
            'created_at' => '2026-10-20 12:00:00', 'updated_at' => '2026-10-20 12:00:00',
        ]);
        $before = DB::table('mobile_biometric_sessions')->first();
        Schema::drop('mobile_biometric_tokens');
        $migration->up();
        $this->assertEquals($before, DB::table('mobile_biometric_sessions')->first());
        $this->assertTrue(Schema::hasTable('mobile_biometric_tokens'));
        DB::table('mobile_biometric_tokens')->insert([
            'session_id' => $before->id, 'token_hash' => hash('sha256', 'schema-test'),
            'created_at' => '2026-10-20 12:00:01',
        ]);
        $token = DB::table('mobile_biometric_tokens')->first();
        $migration->up();
        $this->assertEquals($before, DB::table('mobile_biometric_sessions')->first());
        $this->assertEquals($token, DB::table('mobile_biometric_tokens')->first());
    }

    public function test_mysql_token_creation_uses_datetime_without_an_implicit_timestamp_default()
    {
        config()->set('database.connections.biometric_schema_mysql', [
            'driver' => 'mysql', 'database' => 'unused', 'prefix' => '',
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
        ]);
        config()->set('database.default', 'biometric_schema_mysql');
        $queries = DB::pretend(function () { (new \CreateMobileBiometricSessions())->up(); });
        $sql = collect($queries)->pluck('query')->first(function ($query) {
            return strpos($query, 'create table `mobile_biometric_tokens`') === 0;
        });
        $this->assertNotNull($sql);
        $this->assertStringContainsString('`created_at` datetime not null', $sql);
        $this->assertStringNotContainsString('`created_at` timestamp', $sql);
    }
}
