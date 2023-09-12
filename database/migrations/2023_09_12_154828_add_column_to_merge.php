<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToMerge extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_closing_merges', function (Blueprint $table) {
            $table->date('closing_date')->nullable()->after("schedule_return_date");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_closing_merges', function (Blueprint $table) {
            $table->dropColumn('closing_date');
        });
    }
}
