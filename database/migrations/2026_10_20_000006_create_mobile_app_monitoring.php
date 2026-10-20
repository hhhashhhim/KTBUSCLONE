<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileAppMonitoring extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('mobile_app_login_events')) {
            Schema::create('mobile_app_login_events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('passenger_account_id');
                $table->string('kind', 12);
                $table->boolean('first_login');
                $table->dateTime('occurred_at'); // UTC; independent of each server's timezone.
                $table->index(['company_id', 'occurred_at'], 'mobile_logins_company_time');
                $table->index(['company_id', 'passenger_account_id', 'occurred_at'], 'mobile_logins_account_time');
            });
        }
        if (!Schema::hasTable('mobile_app_runtime')) {
            Schema::create('mobile_app_runtime', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->primary();
                $table->unsignedBigInteger('terminal_id')->nullable();
                $table->unsignedBigInteger('booking_user_id')->nullable();
                $table->dateTime('tracking_started_at'); // UTC; no historical backfill.
                $table->dateTime('last_seen_at');
            });
        }
    }

    public function down()
    {
        // Preserve monitoring history when rolling application code back.
    }
}
