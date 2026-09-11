<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTravelEyeApiLogsTable extends Migration
{
    public function up()
    {
        Schema::create('travel_eye_api_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id')->unique();
            $table->unsignedBigInteger('terminal_id')->index();
            $table->string('voucher')->index();
            $table->string('endpoint');
            $table->json('request_payload')->nullable();
            $table->longText('response_payload')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('exception_message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('travel_eye_api_logs');
    }
}
