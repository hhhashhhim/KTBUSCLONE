<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableCounterExpensesAddBillAndCashbankOptionColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('counter_expenses', function (Blueprint $table) {
            $table->string('bill_post')->default(0)->after('amount');
            $table->string('payment_method')->default(0)->after('bill_post');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('counter_expenses', function (Blueprint $table) {
           $table->dropColumn(['bill_post', 'payment_method']);
        });
    }
}
