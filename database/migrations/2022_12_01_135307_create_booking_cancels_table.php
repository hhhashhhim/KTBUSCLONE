<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingCancelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_cancels', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->nullable();
            $table->integer('ticket_id')->nullable();
            $table->integer('percentage')->nullable();
            $table->string('reason')->nullable();
            $table->integer('added_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('time')->useCurrent();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('booking_cancels');
    }
}
