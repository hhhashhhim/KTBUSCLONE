<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HardenMobileBookingAndTokenSchema extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('mobile_booking_quotes', 'invoice_id')) {
            Schema::table('mobile_booking_quotes', function (Blueprint $table) {
                // A consumed quote is the immutable passenger snapshot for its invoice.
                $table->unsignedBigInteger('invoice_id')->nullable()->unique();
            });
        }
        if (Schema::hasTable('personal_access_tokens') && !Schema::hasColumn('personal_access_tokens', 'expires_at')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable();
            });
        }
    }

    public function down()
    {
        // Keep booking links and token expiry data when rolling back application code.
    }
}
