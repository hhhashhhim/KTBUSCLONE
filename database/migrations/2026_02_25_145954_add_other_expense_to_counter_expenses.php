<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOtherExpenseToCounterExpenses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('counter_expenses', function (Blueprint $table) {
            $table->string('type')->default('category')->after('category_id');
            $table->string('other_income')->nullable()->after('type');
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
                'type',
                'other_income'
            ]);
        });
    }
}
