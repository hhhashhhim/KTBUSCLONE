<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesForRouteUpdatePerformance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('routes_fares', function (Blueprint $table) {
            $table->index(['route_id', 'company_id'], 'routes_fares_route_company_index');
        });

        Schema::table('terminal_visibilities', function (Blueprint $table) {
            $table->index(['route_id', 'company_id'], 'terminal_visibilities_route_company_index');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->index(['company_id', 'route_id'], 'schedules_company_route_index');
        });

        Schema::table('schedule_details', function (Blueprint $table) {
            $table->index(['schedule_id', 'schedule_date'], 'schedule_details_schedule_date_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('schedule_details', function (Blueprint $table) {
            $table->dropIndex('schedule_details_schedule_date_index');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('schedules_company_route_index');
        });

        Schema::table('terminal_visibilities', function (Blueprint $table) {
            $table->dropIndex('terminal_visibilities_route_company_index');
        });

        Schema::table('routes_fares', function (Blueprint $table) {
            $table->dropIndex('routes_fares_route_company_index');
        });
    }
}
