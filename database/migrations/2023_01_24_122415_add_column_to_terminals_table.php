<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToTerminalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('terminals', function (Blueprint $table) {
            $table->decimal('fixed_commission', 10, 2)->after('is_main')->nullable();
            $table->decimal('ticket_flat_commission', 10, 2)->after('fixed_commission')->nullable();
            $table->decimal('ticket_percentage_commission', 10, 2)->after('ticket_flat_commission')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('terminals', function (Blueprint $table) {
            $table->dropColumn('fixed_commission');
            $table->dropColumn('ticket_flat_commission');
            $table->dropColumn('ticket_percentage_commission');
        });
    }
}