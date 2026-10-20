<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileBiometricSessions extends Migration
{
    public function up()
    {
        Schema::create('mobile_biometric_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('passenger_account_id');
            // Deleting the existing Sanctum token in ERP immediately revokes renewal.
            $table->unsignedBigInteger('access_token_id')->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['company_id', 'passenger_account_id'], 'mobile_bio_account');
        });
        Schema::create('mobile_biometric_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id')->index();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_biometric_tokens');
        Schema::dropIfExists('mobile_biometric_sessions');
    }
}
