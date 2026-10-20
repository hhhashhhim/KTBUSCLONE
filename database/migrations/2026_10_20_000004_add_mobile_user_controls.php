<?php

use App\Support\CallCenterPermissions;
use App\Support\MobileAppPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddMobileUserControls extends Migration
{
    public function up()
    {
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
        foreach (['companies' => 'modules', 'roles' => 'permissions'] as $table => $field) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $field)) { continue; }
            DB::table($table)->orderBy('id')->each(function ($row) use ($table, $field) {
                $modules = json_decode($row->$field ?? '[]', true) ?: [];
                $modules = $table === 'roles' && CallCenterPermissions::isAdminRole($row->name ?? null)
                    ? MobileAppPermissions::forCompanyAdmin($modules) : MobileAppPermissions::append($modules, $table === 'companies');
                DB::table($table)->where('id', $row->id)->update([$field => json_encode($modules)]);
            });
        }
    }

    public function down()
    {
        // Keep restrictions, blacklist identities, usage history and configured grants on rollback.
    }
}
