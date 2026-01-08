<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTicketClosingShortagesAddNew extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('ticket_closing_shortages', function (Blueprint $table) {
            $table->decimal('elt')->default(0)->after('kt_commission');
            $table->decimal('cancellation_amount')->default(0)->after('elt');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_closing_shortages', function (Blueprint $table) {
           $table->dropColumn(['elt', 'cancellation_amount']);
        });
    }
}
