<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotelFoodDealDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hotel_food_deal_details', function (Blueprint $table) {
            $table->id();
            $table->integer('food_id');
            $table->integer('food_deal_id');
            $table->integer('quantity');
            $table->integer('hotel_id');
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
        Schema::dropIfExists('hotel_food_deal_details');
    }
}
