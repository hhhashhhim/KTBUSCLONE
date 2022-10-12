<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusSeatMapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_seat_maps', function (Blueprint $table) {
            $table->id();
            $table->integer('bus_id');
            $table->integer('no_of_rows')->nullable();
            $table->integer('row_index')->nullable();
            $table->integer('no_of_cols')->nullable();
            $table->integer('col_index')->nullable();
            $table->string('status')->nullable();
            // $table->json('seat_map')->nullable();
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
        Schema::dropIfExists('bus_seat_maps');
    }
}
