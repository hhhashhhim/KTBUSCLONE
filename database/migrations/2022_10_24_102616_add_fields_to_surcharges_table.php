<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToSurchargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('surcharges', function (Blueprint $table) {
            $table->string('type')->after('name')->nullable();
            $table->renameColumn('percentage', 'amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('surcharges', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->renameColumn('percentage','amount');
        });
    }
}
