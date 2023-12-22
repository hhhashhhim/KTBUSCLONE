<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminalTimeDifferencesTable extends Migration
{
    public function up()
    {
        Schema::create('terminal_time_differences', function (Blueprint $table) {
            $table->id();
            $table->string('time_difference')->nullable();
            $table->integer('city_id')->nullable();
            $table->integer('terminal_id')->nullable();
            $table->integer('route_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('added_by')->nullable();
            $table->timestamp('time')->useCurrent();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('terminal_time_differences');
    }
}
