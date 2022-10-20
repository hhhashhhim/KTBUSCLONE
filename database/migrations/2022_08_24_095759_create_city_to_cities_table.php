<?php

use App\Models\City;
use App\Models\CityToCity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCityToCitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('city_to_city', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('departure_city_id');
            $table->bigInteger('destination_city_id');
            $table->integer('company_id')->nullable();
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
        Schema::dropIfExists('city_to_city');
    }
}
