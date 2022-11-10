<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsOverIssuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets_over_issues', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->nullable();
            $table->integer('old_customer_id')->nullable();
            $table->integer('new_customer_id')->nullable();
            $table->integer('schedule_id')->nullable();
            $table->integer('seat_no')->nullable();
            $table->integer('old_booking_no')->nullable();
            $table->integer('new_booking_no')->nullable();
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
        Schema::dropIfExists('tickets_over_issues');
    }
}
