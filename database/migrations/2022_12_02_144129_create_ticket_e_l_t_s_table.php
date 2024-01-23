<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketELTSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_e_l_t_s', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->nullable();
            $table->integer('ticket_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('departure_city')->nullable();
            $table->integer('destination_city')->nullable();
            $table->integer('schedule_id')->nullable();
            $table->integer('seat_no')->nullable();
            $table->date('date')->nullable();
            $table->integer('elt_price')->nullable();
            $table->integer('seat_fare')->nullable();
            $table->integer('elt_weight')->nullable();
            $table->string('elt_description')->nullable();
            $table->integer('added_by')->nullable();
            $table->integer('updated_by')->nullable();
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
        Schema::dropIfExists('ticket_e_l_t_s');
    }
}
