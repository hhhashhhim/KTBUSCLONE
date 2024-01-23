<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketClosingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_closings', function (Blueprint $table) {
            $table->id();
            $table->integer('bus_id');
            $table->integer('ticket_merge_id');
            $table->integer('schedule_id');
            $table->date('schedule_date');
            $table->time('schedule_time');
            $table->integer('schedule_start')->comment('city_id');
            $table->integer('schedule_end')->comment('city_id');
            $table->integer('schedule_return')->default(0)->comment('0/no, 1/return');
            $table->text('description')->nullable();
            $table->integer('company_id');
            $table->integer('added_by')->nullable();
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
        Schema::dropIfExists('ticket_closings');
    }
}
