<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFareTablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fare_tables', function (Blueprint $table) {
            $table->id();
            $table->decimal('fare',12,2);
            $table->integer('fare_class');
            $table->integer('from_city_id');
            $table->integer('to_city_id');
            $table->integer('company_id');
//            $table->decimal('commission_flat',12,2)->nullable();
//            $table->decimal('commission_percentage',12,2)->nullable();
//            $table->decimal('terminal_commission',12,2)->nullable();
            $table->string('time_difference')->nullable();
            $table->integer('distance_in_km')->nullable();
//            $table->decimal('surcharge',12,2)->nullable();
//            $table->date('surcharge_start_date')->nullable();
//            $table->string('surcharge_end_date')->nullable();
//            $table->decimal('advance_availability',12,2)->nullable();
            $table->tinyInteger('is_active')->default('0');
            $table->integer('added_by');
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
        Schema::dropIfExists('fare_tables');
    }
}
