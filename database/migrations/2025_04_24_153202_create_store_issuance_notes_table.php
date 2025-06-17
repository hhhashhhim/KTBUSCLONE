<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreIssuanceNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_issuance_notes', function (Blueprint $table) {
            $table->id();
            $table->string('requested_by'); 
            $table->integer('mr_id');
            $table->string('status'); //1=uncomplete 2=completed
            $table->string('added_by');
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
        Schema::dropIfExists('store_issuance_notes');
    }
}
