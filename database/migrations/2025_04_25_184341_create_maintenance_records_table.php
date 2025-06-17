<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaintenanceRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id(); 
            $table->integer('product_id');
            $table->integer('qty')->default(1);
            $table->string('maintenance_type');
            $table->text('description')->nullable();
            $table->dateTime('maintenance_date');
            $table->dateTime('next_due_date')->nullable();
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
        Schema::dropIfExists('maintenance_records');
    }
}
