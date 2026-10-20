<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingPassengerRegistrations extends Migration
{
    public function up()
    {
        Schema::create('pending_passenger_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            // Each attempt has its own secret/session. A stranger cannot reserve a number.
            $table->string('mobile', 15);
            $table->string('full_name', 150);
            $table->string('cnic', 13);
            $table->string('email', 190)->nullable();
            $table->string('password');
            $table->string('otp_digest')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->unsignedTinyInteger('otp_attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->index(['company_id', 'mobile']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pending_passenger_registrations');
    }
}
