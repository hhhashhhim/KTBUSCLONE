<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OnlineTerminalDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
           $table->integer('online_terminal')->nullable()->after('type');
           $table->integer('ticket_merge_id')->nullable()->after('gender');
           $table->string('terminal_name')->nullable()->after('is_partial');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('online_terminal');
            $table->dropColumn('ticket_merge_id');
            $table->dropColumn('terminal_name');
        });
    }
}
