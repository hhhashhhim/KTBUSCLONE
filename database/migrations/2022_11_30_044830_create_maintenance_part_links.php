<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaintenancePartLinks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('maintenance_part_links', function (Blueprint $table) {
            $table->id();
            $table->integer('bus_id')->nullable();
            $table->integer('part_id')->nullable();
            $table->integer('maintenance_after')->nullable();
            $table->integer('maintenance_at')->nullable();
            $table->date('maintenance_date')->nullable();
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
        Schema::dropIfExists('maintenance_part_links');
    }
}
