<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToTicketReschedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_reschedules', function (Blueprint $table) {
            $table->integer('old_ticket_id')->nullable()->after('schedule_id');
            $table->integer('new_ticket_id')->nullable()->after('reSchedule_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_reschedules', function (Blueprint $table) {
            $table->dropColumn('old_ticket_id');
            $table->dropColumn('new_ticket_id');
        });
    }
}
