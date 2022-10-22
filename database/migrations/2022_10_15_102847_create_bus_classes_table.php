<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(1);
            $table->integer('company_id')->nullable();
            $table->integer('no_of_seats')->nullable();
            $table->json('seat_map')->nullable();
            $table->integer('no_of_rows')->nullable();
            $table->integer('no_of_cols')->nullable();
            $table->integer('added_by')->nullable();
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
        Schema::dropIfExists('bus_classes');
    }
}
