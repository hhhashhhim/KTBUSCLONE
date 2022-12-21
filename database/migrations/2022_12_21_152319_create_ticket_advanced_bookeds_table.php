<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketAdvancedBookedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_advanced_bookeds', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('ticket_id')->nullable();
            $table->integer('schedule_id')->nullable();
            $table->integer('departure_city_id')->nullable();
            $table->integer('destination_city_id')->nullable();
            $table->integer('booking_no')->nullable();
            $table->date('date')->nullable();
            $table->integer('seat_no')->nullable();
            $table->integer('seat_fare')->nullable();
            $table->integer('gender')->nullable();
            $table->string('type')->nullable();
            $table->integer('added_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('time')->useCurrent();
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
        Schema::dropIfExists('ticket_advanced_bookeds');
    }
}
