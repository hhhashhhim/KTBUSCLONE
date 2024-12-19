<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColoumnToTerminalVisibilities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('terminal_visibilities', function (Blueprint $table) {
            $table->integer('booking_minutes')->after('online_visibilty')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('terminal_visibilities', function (Blueprint $table) {
            $table->dropColumn('booking_minutes');
        });
    }
}
