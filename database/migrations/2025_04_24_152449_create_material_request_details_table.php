<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialRequestDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('material_request_details', function (Blueprint $table) {
            $table->id();
            $table->integer('mr_id');
            $table->integer('product_id');
            $table->integer('qty');
            $table->integer('store_Issued_qty')->nullable();
            $table->string('reason');
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
        Schema::dropIfExists('material_request_details');
    }
}
