<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileAppConfigsTable extends Migration
{
    public function up()
    {
        Schema::create('mobile_app_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();
            $table->string('latest_version')->default('1.0.0');
            $table->string('minimum_supported_version')->default('1.0.0');
            $table->boolean('force_update')->default(false);
            $table->boolean('maintenance_mode')->default(false);
            $table->text('maintenance_message')->nullable();
            $table->string('android_store_url')->nullable();
            $table->string('ios_store_url')->nullable();
            $table->string('support_phone')->nullable();
            $table->string('support_whatsapp')->nullable();
            $table->string('support_email')->nullable();
            $table->json('payment_methods')->nullable();
            $table->json('features')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_app_configs');
    }
}
