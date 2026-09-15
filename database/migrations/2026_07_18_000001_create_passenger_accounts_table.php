<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePassengerAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('passenger_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('company_id');
            $table->string('mobile', 20);
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('gender', 20)->nullable();
            $table->timestamp('mobile_verified_at')->nullable();
            $table->string('otp_digest')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->string('password_reset_otp_digest')->nullable();
            $table->timestamp('password_reset_otp_expires_at')->nullable();
            $table->string('password_reset_token_digest')->nullable();
            $table->timestamp('password_reset_token_expires_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['company_id', 'mobile']);
            $table->unique('customer_id');
            $table->index('email');
        });
    }

    public function down()
    {
        Schema::dropIfExists('passenger_accounts');
    }
}
