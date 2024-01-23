<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketClosingMerges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_closing_merges', function (Blueprint $table) {
            $table->id();
            $table->integer('bus_id');
            $table->date('schedule_departure_date');
            $table->date('schedule_return_date')->nullable();
            $table->integer('schedule_complete')->default(0)->comment('0/no, 1/complete');
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
        Schema::dropIfExists('ticket_closing_merges');
    }
}
