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
            $table->timestamps();
        });
        
          $cities = City::get();
          foreach ($cities as $i => $cityFrom) {
            foreach ($cities as $i => $cityTo) {

                CityToCity::create([
                    'departure_city_id'=>$cityFrom->id,
                    'destination_city_id'=>$cityTo->id,
                ]);
                
            }
             
          }
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
