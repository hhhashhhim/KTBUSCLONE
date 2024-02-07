<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnInRescheduleExtraAmount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reschedule_extra_amounts', function (Blueprint $table) {
            $table->string('old_seat_no')->change();
            $table->string('new_seat_no')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reschedule_extra_amounts', function (Blueprint $table) {
            $table->integer('old_seat_no')->change();
            $table->integer('new_seat_no')->change();
        });
    }
}
