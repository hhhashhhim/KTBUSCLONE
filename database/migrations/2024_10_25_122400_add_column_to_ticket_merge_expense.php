<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddColumnToTicketMergeExpense extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_merge_expenses', function (Blueprint $table) {
            $table->decimal('paid',12,2)->after('amount')->nullable();
            $table->integer('ledger')->after('paid')->default(0);
        });

        DB::statement('
            UPDATE ticket_merge_expenses 
            SET paid = amount
        ');

        Schema::table('ticket_merge_expenses', function (Blueprint $table) {
            $table->string('paid')->nullable(false)->change();
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
            $table->dropColumn('paid');
            $table->dropColumn('ledger');
        });
    }
}
