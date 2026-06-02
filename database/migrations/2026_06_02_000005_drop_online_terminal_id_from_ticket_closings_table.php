<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropOnlineTerminalIdFromTicketClosingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_closings', function (Blueprint $table) {
            $table->dropColumn('online_terminal_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_closings', function (Blueprint $table) {
            $table->unsignedBigInteger('online_terminal_id')->nullable()->after('description')->index();
        });
    }
}
