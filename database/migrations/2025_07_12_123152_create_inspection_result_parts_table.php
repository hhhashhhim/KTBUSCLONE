<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInspectionResultPartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inspection_result_parts', function (Blueprint $table) {
            $table->id();
            $table->integer('inspection_result_id')->nullable();
            $table->integer('fault_claim_id');
            $table->integer('bus_id')->nullable();
            $table->integer('part_id')->nullable();
            $table->integer('added_by')->nullable();
            $table->integer('company_id')->nullable();
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
        Schema::dropIfExists('inspection_result_parts');
    }
}
