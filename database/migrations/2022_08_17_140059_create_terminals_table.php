<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('terminals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact');
            $table->text('address');
            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->string('time_difference')->nullable();
            $table->string('advance_booking')->nullable();
            $table->string('available_seats')->nullable();
            $table->tinyInteger('active_sms')->nullable();
            $table->integer('city_id');
            $table->integer('company_id');
            $table->string('online_terminal_name');
            $table->string('status')->default('inactive');
            $table->integer('added_by');
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
        Schema::dropIfExists('terminals');
    }
}
