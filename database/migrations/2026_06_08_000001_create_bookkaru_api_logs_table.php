<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookkaruApiLogsTable extends Migration
{
    public function up()
    {
        Schema::create('bookkaru_api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->nullable()->index();
            $table->string('invoice_id')->nullable()->index();
            $table->string('booking_reference')->nullable()->index();
            $table->json('seat_numbers')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('status')->nullable()->index();
            $table->boolean('success')->default(false);
            $table->boolean('duplicate')->default(false);
            $table->boolean('unauthorized')->default(false);
            $table->text('exception_message')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookkaru_api_logs');
    }
}
