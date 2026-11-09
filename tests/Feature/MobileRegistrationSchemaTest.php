<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobileRegistrationSchemaTest extends TestCase
{
    private $mysql;

    protected function setUp(): void
    {
        parent::setUp();
        require_once database_path('migrations/2026_10_21_000009_create_pending_passenger_registrations.php');
    }

    protected function tearDown(): void
    {
        if ($this->mysql) {
            $this->mysql->statement('DROP TEMPORARY TABLE IF EXISTS `pending_passenger_registrations`');
        }
        DB::purge('registration_schema_testing');
        parent::tearDown();
    }

    public function test_mysql_migration_has_no_implicit_default_for_the_required_deadline(): void
    {
        config(['database.default' => 'registration_schema_testing',
            'database.connections.registration_schema_testing' => [
                'driver' => 'mysql', 'database' => 'unused', 'prefix' => '',
                'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
            ],
        ]);
        $queries = DB::pretend(function () { (new \CreatePendingPassengerRegistrations())->up(); });
        $sql = $queries[0]['query'];
        $this->assertStringContainsString('`expires_at` datetime not null', $sql);
        $this->assertStringNotContainsString('`expires_at` timestamp', $sql);
        $this->assertStringNotContainsString('current_timestamp', strtolower($sql));
    }

    public function test_strict_legacy_mysql_accepts_signup_and_preserves_expiry_during_resend(): void
    {
        if (!getenv('MOBILE_MYSQL_TEST_DATABASE')) {
            $this->markTestSkipped('Set MOBILE_MYSQL_TEST_DATABASE to run the temporary-table MySQL regression.');
        }
        // Explicit test-only connection. Never use the application environment database.
        config(['database.default' => 'registration_schema_testing',
            'database.connections.registration_schema_testing' => [
                'driver' => 'mysql', 'host' => getenv('MOBILE_MYSQL_TEST_HOST') ?: '127.0.0.1',
                'port' => getenv('MOBILE_MYSQL_TEST_PORT') ?: '3306',
                'database' => getenv('MOBILE_MYSQL_TEST_DATABASE'),
                'username' => getenv('MOBILE_MYSQL_TEST_USERNAME') ?: 'root',
                'password' => getenv('MOBILE_MYSQL_TEST_PASSWORD') ?: '',
                'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '',
                'strict' => true,
            ],
        ]);
        $this->mysql = DB::connection();
        DB::statement('SET SESSION explicit_defaults_for_timestamp = 0');
        $queries = DB::pretend(function () { (new \CreatePendingPassengerRegistrations())->up(); });
        $create = preg_replace('/^create table /i', 'create temporary table ', $queries[0]['query']);

        // Reproduce the exact strict-mode failure before checking the corrected migration.
        try {
            DB::statement(str_replace('`expires_at` datetime not null', '`expires_at` timestamp not null', $create));
            $this->fail('The original schema must reproduce the invalid timestamp default.');
        } catch (QueryException $exception) {
            $this->assertSame(1067, (int) $exception->errorInfo[1]);
        }
        DB::statement($create);
        foreach (array_slice($queries, 1) as $query) { DB::statement($query['query']); }

        $deadline = '2030-11-06 18:05:00';
        DB::table('pending_passenger_registrations')->insert([
            'company_id' => 1, 'mobile' => '923001234567', 'full_name' => 'Schema Test',
            'cnic' => '3520212345671', 'password' => 'test-only-hash', 'expires_at' => $deadline,
        ]);
        DB::table('pending_passenger_registrations')->update([
            'otp_digest' => 'new-test-digest', 'otp_attempts' => 0,
            'otp_expires_at' => '2030-11-05 18:15:00', 'updated_at' => '2030-11-05 18:05:00',
        ]);
        $this->assertSame($deadline, DB::table('pending_passenger_registrations')->value('expires_at'));
        $column = DB::selectOne("SHOW COLUMNS FROM `pending_passenger_registrations` WHERE Field = 'expires_at'");
        $this->assertSame('datetime', $column->Type);
        $this->assertSame('NO', $column->Null);
        $this->assertNull($column->Default);
        $this->assertStringNotContainsString('on update', strtolower((string) $column->Extra));
        $this->assertStringNotContainsString('default_generated', strtolower((string) $column->Extra));
        $this->assertNotEmpty(DB::select("SHOW INDEX FROM `pending_passenger_registrations` WHERE Column_name = 'expires_at'"));
    }
}
