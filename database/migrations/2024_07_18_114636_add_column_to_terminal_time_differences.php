<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToTerminalTimeDifferences extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('terminal_time_differences', function (Blueprint $table) {
            $table->integer('show')->after("route_id")->default(0);
            $table->string('display_name')->after("id")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('terminal_time_differences', function (Blueprint $table) {
            $table->dropColumn('show');
            $table->dropColumn('display_name');
        });
    }
}
