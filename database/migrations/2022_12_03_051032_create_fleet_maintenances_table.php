<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFleetMaintenancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fleet_maintenances', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('bus_id');
            $table->bigInteger('part_id');
            $table->decimal('amount',12,2);
            $table->decimal('company_paid',12,2)->nullable();
            $table->decimal('driver_paid',12,2)->nullable();
            $table->string('evidence')->nullable();
            $table->string('detail')->nullable();
            $table->enum('maintenance_type', ['0', '1'])->default('0')->comment('0/due, 1/irregular');
            $table->integer('company_id')->nullable();
            $table->integer('added_by')->nullable();
            $table->integer('updated_by')->nullable();
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
        Schema::dropIfExists('fleet_maintenances');
    }
}
