<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileBookingQuotesTable extends Migration
{
    public function up()
    {
        Schema::create('mobile_booking_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->unsignedBigInteger('passenger_account_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('schedule_detail_id');
            $table->json('payload');
            $table->decimal('base_fare', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('taxes', 12, 2)->default(0);
            $table->decimal('fees', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['passenger_account_id', 'expires_at']);
            $table->index(['company_id', 'schedule_detail_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_booking_quotes');
    }
}
