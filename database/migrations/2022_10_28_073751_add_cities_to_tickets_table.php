<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCitiesToTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->integer('departure_city_id')->after('company_id');
            $table->integer('destination_city_id')->after('departure_city_id');
            $table->integer('is_partial')->default(0)->after('gender');
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
            $table->dropColumn('departure_city_id');
            $table->dropColumn('destination_city_id');
            $table->dropColumn('is_partial');
        });
    }
}
