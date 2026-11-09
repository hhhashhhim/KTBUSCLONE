<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SupportMobileScheduleQuotaRules extends Migration
{
    public function up()
    {
        // These are shared ERP fields, not a second mobile quota configuration.
        // Preserve all settings when the ERP migrations have already installed them.
        if (!Schema::hasColumn('schedules', 'intermediate_quota')) {
            Schema::table('schedules', function (Blueprint $table) { $table->unsignedInteger('intermediate_quota')->nullable(); });
        }
        if (!Schema::hasColumn('schedules', 'quota_exempt_routes')) {
            Schema::table('schedules', function (Blueprint $table) { $table->json('quota_exempt_routes')->nullable(); });
        }
        if (!Schema::hasColumn('tickets', 'quota_discount')) {
            Schema::table('tickets', function (Blueprint $table) { $table->decimal('quota_discount', 12, 2)->default(0); });
        }
        if (!Schema::hasTable('schedule_quota_terminal_discounts')) {
            Schema::create('schedule_quota_terminal_discounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('schedule_id');
                $table->unsignedBigInteger('terminal_id');
                $table->decimal('percentage', 5, 2);
                $table->boolean('active')->default(true);
                $table->unsignedBigInteger('updated_by');
                $table->string('migration_note')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'schedule_id', 'terminal_id'], 'schedule_quota_discount_terminal');
            });
        }
    }

    public function down()
    {
        // Shared ERP settings and ticket audit amounts must survive a mobile rollback.
    }
}
