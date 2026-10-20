<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMobilePassengerControlPolicies extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('mobile_app_passenger_controls')) {
            Schema::create('mobile_app_passenger_controls', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('passenger_account_id');
                $table->string('status', 20)->default('active');
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->unique(['company_id', 'passenger_account_id'], 'mobile_app_passenger_controls_company_account_unique');
            });
        }
        foreach (['daily_seat_limit', 'monthly_seat_limit', 'otp_daily_limit', 'otp_monthly_limit'] as $column) {
            if (!Schema::hasColumn('mobile_app_passenger_controls', $column)) {
                Schema::table('mobile_app_passenger_controls', function (Blueprint $table) use ($column) { $table->unsignedInteger($column)->nullable(); });
            }
        }
        foreach (['bookings_enabled' => true, 'otp_enabled' => true, 'password_reset_required' => false] as $column => $default) {
            if (!Schema::hasColumn('mobile_app_passenger_controls', $column)) {
                Schema::table('mobile_app_passenger_controls', function (Blueprint $table) use ($column, $default) { $table->boolean($column)->default($default); });
            }
        }
        if (!Schema::hasTable('mobile_app_policies')) {
            Schema::create('mobile_app_policies', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('company_id')->unique(); $table->json('settings');
                $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps();
            });
        }
        if (!Schema::hasTable('mobile_app_blacklist_entries')) {
            Schema::create('mobile_app_blacklist_entries', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('passenger_account_id');
                $table->string('kind', 10); $table->string('identity_hash', 64); $table->timestamp('created_at');
                $table->index(['company_id', 'identity_hash'], 'mobile_blacklist_identity');
                $table->unique(['company_id', 'passenger_account_id', 'kind'], 'mobile_blacklist_account_kind');
            });
        }
        if (!Schema::hasTable('mobile_app_seat_usage')) {
            Schema::create('mobile_app_seat_usage', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('passenger_account_id');
                $table->unsignedBigInteger('invoice_id')->unique(); $table->unsignedInteger('seats'); $table->dateTime('booked_at');
                $table->index(['company_id', 'passenger_account_id', 'booked_at'], 'mobile_seat_usage_account_time');
            });
        }

    }

    public function down()
    {
        // Keep restrictions, blacklist identities, usage history and configured grants on rollback.
    }
}
