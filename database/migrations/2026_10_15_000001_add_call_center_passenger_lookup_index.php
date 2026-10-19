<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCallCenterPassengerLookupIndex extends Migration
{
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['company_id', 'customer_id', 'terminal_id'], 'tickets_cc_passenger_scope_idx');
        });
    }

    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_cc_passenger_scope_idx');
        });
    }
}
