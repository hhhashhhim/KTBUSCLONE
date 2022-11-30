<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddColumnToTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->integer('total_fare')->after('seat_no')->nullable();
            $table->integer('total_receivable')->after('discount')->nullable();
            DB::statement("ALTER TABLE `tickets` CHANGE `status` `type` ENUM('booked','advance booking','cancel', 'over_issue', 'reschedule') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL");
            $table->integer('seat_fare')->after('total_fare')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::table('tickets', function (Blueprint $table) {
//          $table->dropColumn('type');
//        });
    }
}
