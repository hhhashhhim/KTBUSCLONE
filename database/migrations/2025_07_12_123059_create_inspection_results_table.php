<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInspectionResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inspection_results', function (Blueprint $table) {
            $table->id();
            $table->integer('fault_claim_id');
            $table->integer('dock_request_id')->nullable();
            $table->integer('bus_id')->nullable();
            $table->integer('driver_id')->nullable();
            $table->string('status');
            $table->string('repair_type')->nullable();
            $table->string('machanic_name')->nullable();
            $table->integer('current_reading')->nullable();
            $table->date('maintenance_date')->nullable();
            $table->integer('vendor_id')->nullable();
            $table->decimal('amount',15,2)->nullable();
            $table->text('comments')->nullable();
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
        Schema::dropIfExists('inspection_results');
    }
}
