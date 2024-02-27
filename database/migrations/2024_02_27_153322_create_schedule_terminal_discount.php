<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduleTerminalDiscount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schedule_terminal_discounts', function (Blueprint $table) {
            $table->id();
            $table->integer('schedule_id');
            $table->integer('discount_id');
            $table->integer('terminal_id');
            $table->integer('allow')->default(0);
            $table->integer('added_by')->nullable();
            $table->integer('company_id')->nullable();
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
        Schema::dropIfExists('schedule_terminal_discounts');
    }
}
