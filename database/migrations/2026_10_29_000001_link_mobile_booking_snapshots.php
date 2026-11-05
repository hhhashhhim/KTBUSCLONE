<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LinkMobileBookingSnapshots extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('mobile_booking_quotes', 'invoice_id')) {
            Schema::table('mobile_booking_quotes', function (Blueprint $table) {
                // Preserve submitted passenger details without editing shared customers.
                $table->unsignedBigInteger('invoice_id')->nullable()->unique();
                $table->index(['used_at', 'expires_at', 'id'], 'mobile_quotes_cleanup_index');
            });
        }
    }

    public function down()
    {
        // Retain passenger snapshots when rolling back mobile application code.
    }
}
