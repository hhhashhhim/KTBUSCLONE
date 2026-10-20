<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileAppAdminTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('mobile_app_admin_audits')) {
            Schema::create('mobile_app_admin_audits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('terminal_id')->nullable()->index();
                $table->unsignedBigInteger('route_id')->nullable()->index();
                $table->unsignedBigInteger('actor_id');
                $table->string('action', 60);
                $table->string('subject_type', 40);
                $table->string('subject_id', 64)->nullable();
                $table->text('reason')->nullable();
                $table->json('changes')->nullable();
                $table->dateTime('created_at')->index();
            });
        }
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
    }

    public function down()
    {
        Schema::dropIfExists('mobile_app_passenger_controls');
        Schema::dropIfExists('mobile_app_admin_audits');
    }
}
