<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColoumnTickets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->integer('bus_id')->nullable()->after("company_id");
            $table->integer('ticket_closing_id')->nullable()->after("company_id");
            $table->date('schedule_date')->nullable()->after("booking_no");
            $table->integer('terminal_id')->nullable()->after("company_id");
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
            $table->dropColumn('bus_id');
            $table->dropColumn('ticket_closing_id');
            $table->dropColumn('schedule_date');
            $table->dropColumn('terminal_id');
        });
    }
}
