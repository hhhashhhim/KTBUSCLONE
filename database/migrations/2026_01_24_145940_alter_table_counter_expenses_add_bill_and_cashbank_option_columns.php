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
            $table->string('bill_post')->nullable()->after('amount');
            $table->decimal('total', 15, 2)->after('amount')->default(0);
            $table->decimal('cash_payment', 15, 2)->after('total')->default(0);
            $table->decimal('bank_payment', 15, 2)->after('cash_payment')->default(0);

            // 🧾 Ledgers
            $table->unsignedBigInteger('cash_id')->nullable()->after('bank_payment');
            $table->unsignedBigInteger('bank_id')->nullable()->after('cash_id');

            // 🗂 Category
            $table->unsignedBigInteger('category_id')->after('bank_id');
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
            $table->dropColumn([
                'bill_post',
                'total',
                'cash_payment',
                'bank_payment',
                'cash_id',
                'bank_id',
                'category_id',
            ]);
        });
    }
}
