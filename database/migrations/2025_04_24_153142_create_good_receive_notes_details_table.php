<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoodReceiveNotesDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    { 
        Schema::create('good_receive_note_details', function (Blueprint $table) {
            $table->id();
            $table->integer('good_receive_note_id');
            $table->integer('product_id');
            $table->integer('qty');
            $table->decimal('rate', 10, 2);
            $table->decimal('total', 10, 2);
            $table->decimal('tax', 10, 2);
            $table->decimal('delivery_charges', 10, 2);
            $table->decimal('discount', 10, 2);
            $table->decimal('net_amount', 10, 2);
            $table->string('company_id');

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
        Schema::dropIfExists('good_receive_note_details');
    }
}
