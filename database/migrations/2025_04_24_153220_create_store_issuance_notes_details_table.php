<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreIssuanceNotesDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_issuance_note_details', function (Blueprint $table) {
            $table->id();
            $table->integer('store_issuance_note_id');
            $table->integer('product_id');
            $table->integer('qty');
            $table->decimal('rate', 10, 2); 
            $table->decimal('total', 10, 2);
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
        Schema::dropIfExists('store_issuance_note_details');
    }
}
