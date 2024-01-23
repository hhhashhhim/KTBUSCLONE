<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('bus_class_id')->nullable();
            $table->integer('route_id')->nullable();
//            $table->integer('bus_id')->nullable();
//            $table->json('seat_map')->nullable();
            $table->json('route_city_terminal')->nullable();
            $table->integer('selected_bus_class_id')->nullable();
//            $table->integer('no_of_rows')->nullable();
//            $table->integer('no_of_cols')->nullable();
            $table->integer('surcharge_id')->nullable();
            $table->integer('discount_id')->nullable();
            $table->integer('company_id')->nullable();
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
        Schema::dropIfExists('schedules');
    }
}
