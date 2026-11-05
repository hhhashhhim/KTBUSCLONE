<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileQuoteSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'quote_schema_test', 'database.connections.quote_schema_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        foreach ([
            '2026_07_18_000003_create_mobile_booking_quotes_table.php',
            '2026_10_29_000001_harden_mobile_booking_and_token_schema.php',
            '2026_10_29_000001_link_mobile_booking_snapshots.php',
            '2026_11_06_000002_ensure_mobile_quote_cleanup_index.php',
        ] as $file) {
            require_once database_path('migrations/' . $file);
        }
        (new \CreateMobileBookingQuotesTable())->up();
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token');
        });
        DB::table('personal_access_tokens')->insert(['id' => 1, 'token' => 'test-token']);
        DB::table('mobile_booking_quotes')->insert([
            'id' => 1, 'token' => 'test-quote', 'passenger_account_id' => 1, 'company_id' => 1,
            'schedule_detail_id' => 1, 'payload' => '{}', 'base_fare' => 100, 'total' => 100,
            'expires_at' => '2026-11-06 12:10:00', 'used_at' => '2026-11-06 12:05:00',
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('quote_schema_test');
        parent::tearDown();
    }

    /** @dataProvider migrationOrders */
    public function test_upgrade_preserves_booking_and_token_data_with_cleanup_index(array $migrations): void
    {
        foreach ($migrations as $class) {
            (new $class)->up();
        }
        DB::table('mobile_booking_quotes')->where('id', 1)->update(['invoice_id' => 50]);
        $before = DB::table('mobile_booking_quotes')->first();
        $token = DB::table('personal_access_tokens')->first();

        $repair = new \EnsureMobileQuoteCleanupIndex;
        $repair->up();
        $repair->up();
        $repair->down();

        $this->assertEquals($before, DB::table('mobile_booking_quotes')->first());
        $this->assertEquals($token, DB::table('personal_access_tokens')->first());
        $indexes = DB::connection()->getDoctrineSchemaManager()->listTableIndexes('mobile_booking_quotes');
        $cleanup = array_filter($indexes, function ($index) {
            return $index->getColumns() === ['used_at', 'expires_at', 'id'];
        });
        $invoice = array_filter($indexes, function ($index) {
            return $index->isUnique() && $index->getColumns() === ['invoice_id'];
        });
        $this->assertCount(1, $cleanup);
        $this->assertCount(1, $invoice);
        $this->assertTrue(Schema::hasColumn('personal_access_tokens', 'expires_at'));
    }

    public static function migrationOrders(): array
    {
        return [
            'normal filename order' => [[\HardenMobileBookingAndTokenSchema::class, \LinkMobileBookingSnapshots::class]],
            'snapshot previously deployed alone' => [[\LinkMobileBookingSnapshots::class, \HardenMobileBookingAndTokenSchema::class]],
        ];
    }
}
