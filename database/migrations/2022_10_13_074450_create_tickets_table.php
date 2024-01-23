<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('bus_class_id');
            $table->integer('seat_no');
            $table->integer('customer_id');
            $table->integer('schedule_id');
            $table->text('remarks')->nullable();
            $table->tinyInteger('for_female')->default(0);
            $table->string('type')->nullable();
            $table->decimal('discount',10,2)->nullable();
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
        Schema::dropIfExists('tickets');
    }
}
