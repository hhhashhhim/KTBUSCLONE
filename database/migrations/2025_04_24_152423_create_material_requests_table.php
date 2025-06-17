<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('requested_by');
            $table->string('status'); 
            // 0=Rejected, 1=Proccessing,  2=Issued,   3=PRN Generated    4=Bid Generated 
            // 5=PO   Generated    6=Inward Generated    7=PartialIssuad
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
        Schema::dropIfExists('material_requests');
    }
}
