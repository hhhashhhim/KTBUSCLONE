<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableTicketMergeExpensesAddRoutesCulomns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('ticket_merge_expenses', function (Blueprint $table) {
            $table->bigInteger('start_route_id')->default(0)->after('ticket_merge_id');
            $table->bigInteger('return_route_id')->default(0)->after('start_route_id');
            $table->bigInteger('bus_id')->default(0)->after('return_route_id');

        });
         Schema::table('ticket_closing_shortages', function (Blueprint $table) {
            $table->bigInteger('route_id')->default(0)->after('cancellation_amount');
            $table->bigInteger('bus_id')->default(0)->after('route_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('ticket_merge_expenses', function (Blueprint $table) {
           $table->dropColumn(['start_route_id', 'return_route_id', 'bus_id']);
        });
         Schema::table('ticket_closing_shortages', function (Blueprint $table) {
           $table->dropColumn(['route_id', 'bus_id']);
        });
    }
}
