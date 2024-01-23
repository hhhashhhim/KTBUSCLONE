<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColoumToAllTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('bus_classes', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('cities', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('city_to_city', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('designations', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('fare_tables', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('fleet_maintenance_parts', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('hotels', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('hotel_foods', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('hotel_food_deals', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('hotel_food_deal_details', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('hotel_food_orders', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('leaves', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('reschedule_extra_amounts', function (Blueprint $table) {
            $table->integer('added_by')->nullable()->after('deleted_at');
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('routes', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('routes_fares', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('routes_terminals', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('schedule_details', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('terminals', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('terminal_allowed_seats_advance', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('terminal_available_seats', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('terminal_commissions', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('terminal_discounts', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('terminal_time_differences', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('ticket_closings', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('ticket_closing_members', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('ticket_closing_merges', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('ticket_merge_expenses', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('ticket_reschedules', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
        Schema::table('user_passwords', function (Blueprint $table) {
            $table->integer('updated_by')->nullable()->after('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('bus_classes', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('city_to_city', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('designations', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('fare_tables', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('fleet_maintenance_parts', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('hotel_foods', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('hotel_food_deals', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('hotel_food_deal_details', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('hotel_food_orders', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('reschedule_extra_amounts', function (Blueprint $table) {
            $table->dropColumn('added_by');
            $table->dropColumn('updated_by');
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('routes_fares', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('routes_terminals', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('schedule_details', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('terminals', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('terminal_allowed_seats_advance', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('terminal_available_seats', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('terminal_commissions', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('terminal_discounts', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('terminal_time_differences', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('ticket_closings', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('ticket_closing_members', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('ticket_closing_merges', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('ticket_merge_expenses', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('ticket_reschedules', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        Schema::table('user_passwords', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
    }
}
