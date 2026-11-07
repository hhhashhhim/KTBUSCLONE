<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduleQuotaDiscounts extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('schedule_quota_discounts')) {
            Schema::create('schedule_quota_discounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('schedule_id');
                $table->unsignedBigInteger('user_id');
                $table->decimal('percentage', 5, 2);
                $table->boolean('active')->default(true);
                $table->unsignedBigInteger('updated_by');
                $table->timestamps();
                $table->unique(['company_id', 'schedule_id', 'user_id'], 'schedule_quota_discount_user');
            });
        }
        if (!Schema::hasColumn('tickets', 'quota_discount')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->decimal('quota_discount', 12, 2)->default(0);
            });
        }
    }

    public function down()
    {
        // Preserve configured discounts and ticket accounting on application rollback.
    }
}
