<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnInTicketAdvanceBookeds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_advanced_bookeds', function (Blueprint $table) {
            $table->string('seat_no')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_advanced_bookeds', function (Blueprint $table) {
            $table->integer('seat_no')->change();
        });
    }
}
