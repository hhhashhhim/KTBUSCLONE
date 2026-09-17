<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobilePaymentExpirySchemaTest extends TestCase
{
    private $connection;

    protected function setUp(): void
    {
        parent::setUp();
        if (!getenv('MOBILE_MYSQL_TEST_DATABASE')) {
            $this->markTestSkipped('Set MOBILE_MYSQL_TEST_DATABASE to run the temporary-table MySQL regression.');
        }
        // Explicit opt-in connection; never fall back to the application's .env.
        config()->set('database.connections.mobile_expiry_testing', [
            'driver' => 'mysql', 'host' => getenv('MOBILE_MYSQL_TEST_HOST') ?: '127.0.0.1',
            'port' => getenv('MOBILE_MYSQL_TEST_PORT') ?: '3306',
            'database' => getenv('MOBILE_MYSQL_TEST_DATABASE'),
            'username' => getenv('MOBILE_MYSQL_TEST_USERNAME') ?: 'root',
            'password' => getenv('MOBILE_MYSQL_TEST_PASSWORD') ?: '',
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '',
            'strict' => true,
        ]);
        config()->set('database.default', 'mobile_expiry_testing');
        $this->connection = DB::connection();
        DB::statement("SET SESSION time_zone = '+00:00'");
        require_once base_path('database/migrations/2026_09_12_000008_create_mobile_payments_table.php');
        require_once base_path('database/migrations/2026_09_17_000011_fix_mobile_payment_expiry_column.php');
    }

    protected function tearDown(): void
    {
        if ($this->connection) {
            // Only a session-local temporary table is ever created or dropped.
            $this->connection->statement('DROP TEMPORARY TABLE IF EXISTS `mobile_payments`');
            DB::purge('mobile_expiry_testing');
        }
        parent::tearDown();
    }

    /** @dataProvider schemaVersions */
    public function test_deadline_survives_payment_updates_on_legacy_mysql(bool $legacy)
    {
        $queries = DB::pretend(function () { (new \CreateMobilePaymentsTable())->up(); });
        $created = false;
        $indexes = [];
        foreach ($queries as $query) {
            $sql = $query['query'];
            if (stripos($sql, 'create table ') === 0) {
                $sql = preg_replace('/^create table /i', 'create temporary table ', $sql);
                if ($legacy) {
                    $this->assertStringContainsString('`expires_at` datetime not null', $sql);
                    $sql = str_replace('`expires_at` datetime not null', '`expires_at` timestamp not null default current_timestamp on update current_timestamp', $sql);
                }
                DB::statement($sql);
                $created = true;
            } else {
                $this->assertTrue($created, 'Index operations require our temporary table first.');
                $indexes[] = $sql;
            }
        }
        $this->assertTrue($created);
        $deadline = '2030-09-17 10:10:00';
        $this->insertPayment(1, $deadline);
        DB::table('mobile_payments')->where('id', 1)->update(['checked_at' => '2030-09-17 10:00:02']);
        $oldDeadline = DB::table('mobile_payments')->where('id', 1)->value('expires_at');
        if ($legacy) {
            // Reproduce the cPanel failure before applying the repair.
            $this->assertNotSame($deadline, $oldDeadline);
            DB::table('mobile_payments')->where('id', 1)->update(['status' => 'expired']);
        } else {
            $this->assertSame($deadline, $oldDeadline);
        }
        // MariaDB 10.4 temporary-table ALTERs can suppress automatic updates.
        // Reproduce the failure before adding the original migration's indexes.
        foreach ($indexes as $sql) { DB::statement($sql); }
        $before = DB::table('mobile_payments')->where('id', 1)->first();
        $repair = new \FixMobilePaymentExpiryColumn();
        $repair->up();
        $repair->up(); // The repair is also safe on fresh installs and when rerun.
        $this->assertEquals($before, DB::table('mobile_payments')->where('id', 1)->first());
        $column = DB::selectOne("SHOW COLUMNS FROM `mobile_payments` WHERE Field = 'expires_at'");
        $this->assertSame('datetime', $column->Type);
        $this->assertSame('NO', $column->Null);
        $this->assertNull($column->Default);
        $this->assertSame('', $column->Extra);
        $this->assertNotEmpty(DB::select("SHOW INDEX FROM `mobile_payments` WHERE Column_name = 'expires_at'"));
        $this->insertPayment(2, $deadline);
        foreach (['checked_at', 'started_at', 'updated_at'] as $field) {
            DB::table('mobile_payments')->where('id', 2)->update([$field => '2030-09-17 10:00:03']);
            $this->assertSame($deadline, DB::table('mobile_payments')->where('id', 2)->value('expires_at'));
        }
        $repair->down();
        $this->assertSame('datetime', DB::selectOne("SHOW COLUMNS FROM `mobile_payments` WHERE Field = 'expires_at'")->Type);
    }

    private function insertPayment(int $id, string $deadline): void
    {
        DB::table('mobile_payments')->insert([
            'id' => $id, 'public_id' => 'expiry-test-' . $id, 'company_id' => 1,
            'passenger_account_id' => 1, 'invoice_id' => $id, 'quote_id' => $id,
            'method' => 'jazzcash', 'transaction_reference' => 'expiry-test-' . $id,
            'amount_minor' => 10000, 'currency' => 'PKR', 'status' => 'pending',
            'ticket_count' => 1, 'expires_at' => $deadline, 'created_at' => '2030-09-17 10:00:00',
        ]);
    }

    public static function schemaVersions(): array
    {
        return ['existing cPanel schema' => [true], 'new installation' => [false]];
    }
}
