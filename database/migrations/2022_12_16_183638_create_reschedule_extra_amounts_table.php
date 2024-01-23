<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRescheduleExtraAmountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reschedule_extra_amounts', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->nullable();
            $table->integer('old_ticket_id')->nullable();
            $table->integer('old_seat_no')->nullable();
            $table->integer('new_seat_no')->nullable();
            $table->integer('old_seat_class')->nullable();
            $table->integer('new_seat_class')->nullable();
            $table->integer('old_seat_fare')->nullable();
            $table->integer('new_seat_fare')->nullable();
            $table->string('type')->nullable();
            $table->integer('diff_amount')->nullable();
            $table->integer('old_departure_city_id')->nullable();
            $table->integer('new_departure_city_id')->nullable();
            $table->integer('old_destination_city_id')->nullable();
            $table->integer('new_destination_city_id')->nullable();
            $table->integer('old_schedule_id')->nullable();
            $table->integer('new_schedule_id')->nullable();
            $table->date('old_booking_date')->nullable();
            $table->date('new_booking_date')->nullable();
            $table->timestamp('time')->useCurrent();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reschedule_extra_amounts');
    }
}
