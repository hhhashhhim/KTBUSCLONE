<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoutesFaresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('routes_fares', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('route_id');
            $table->bigInteger('fare_id');
            $table->bigInteger('city_from_id');
            $table->bigInteger('city_to_id');
            $table->bigInteger('company_id');
            $table->bigInteger('added_by');
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
        Schema::dropIfExists('routes_fares');
    }
}
