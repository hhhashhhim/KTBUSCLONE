<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotelFoodOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hotel_food_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('hotel_id');
            $table->integer('item_id');
            $table->integer('item_type')->comment("1/food,2/deal");
            $table->decimal('quantity',12,2);
            $table->decimal('price',12,2);
            $table->decimal('amount',12,2);
            $table->integer('seat_no');
            $table->string('estimated_time');
            $table->integer('ticket_closing_id');
            $table->integer('bus_id');
            $table->integer('schedule_id');
            $table->date('schedule_date');
            $table->string('status')->comment("pending,received,ready,delivered");
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
        Schema::dropIfExists('hotel_food_orders');
    }
}
