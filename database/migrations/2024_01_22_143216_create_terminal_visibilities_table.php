<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminalVisibilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('terminal_visibilities', function (Blueprint $table) {
            $table->id();
            $table->integer('route_id')->nullable();
            $table->integer('departure_city_id')->nullable();
            $table->integer('destination_city_id')->nullable();
            $table->integer('physical_visibilty')->default(0);
            $table->integer('online_visibilty')->default(0);
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
        Schema::dropIfExists('terminal_visibilities');
    }
}
