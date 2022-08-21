<?php

use App\Models\City;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('added_by');
            $table->timestamps();
        });

        $allCitiesOfPak = [
          "Rawalpindi",
          "Karachi",
          "Lahore",
          "Faisalabad",
          "Multan",
          "Dera Ismail Khan",
          "Kot Adu",
          "Layyah",
          "Bhakkar",
          "Bahawalpur",
          "Larkana",
          "Alipur",
          "Arifwala",
          "Sahiwal"
        ];

        foreach ($allCitiesOfPak as $i => $city) {
            City::create([
                'name'=>$city,
                'added_by'=>0,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cities');
    }
}
