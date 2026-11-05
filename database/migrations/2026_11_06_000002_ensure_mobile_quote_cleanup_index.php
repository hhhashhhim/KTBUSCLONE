<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureMobileQuoteCleanupIndex extends Migration
{
    public function up()
    {
        // Both October migrations can add invoice_id. Installing the hardening
        // migration first previously made the snapshot migration skip this index.
        // Repair in a new migration so already-recorded installations also upgrade.
        $connection = Schema::getConnection();
        $indexes = $connection->getDoctrineSchemaManager()->listTableIndexes(
            $connection->getTablePrefix() . 'mobile_booking_quotes'
        );
        foreach ($indexes as $index) {
            if ($index->getColumns() === ['used_at', 'expires_at', 'id']) {
                return;
            }
        }
        Schema::table('mobile_booking_quotes', function (Blueprint $table) {
            $table->index(['used_at', 'expires_at', 'id'], 'mobile_quotes_cleanup_index');
        });
    }

    public function down()
    {
        // Preserve the index when rolling back application code.
    }
}
