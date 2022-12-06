<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketReschedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_reschedules', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->nullable();
            $table->integer('schedule_id')->nullable();
            $table->integer('reSchedule_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->date('date')->nullable();
            $table->date('reschedule_date')->nullable();
            $table->integer('departure_city_id')->nullable();
            $table->integer('reschedule_departure_city_id')->nullable();
            $table->integer('reschedule_destination_city_id')->nullable();
            $table->integer('destination_city_id')->nullable();
            $table->string('reason')->nullable();
            $table->integer('added_by')->useCurrent();
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
        Schema::dropIfExists('ticket_reschedules');
    }
}
