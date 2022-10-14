<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BookingNoToTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {

            $table->integer('booking_no')->after('bus_id');
            $table->string('date')->after('booking_no')->nullable();
            $table->dateTime('time')->after('discount')->nullable();
            $table->integer('added_by')->after('time')->nullable();
            $table->softDeletes()->after('time')->nullable();
            $table->renameColumn('for_female', 'gender');
            
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
            $table->dropColumn('booking_no');
            $table->dropColumn('date');
<<<<<<< HEAD
            $table->dropColumn('time');
            $table->dropColumn('deleted_at');
            $table->dropColumn('added_by');
=======
            $table->renameColumn('gender','for_female');
>>>>>>> Booking
        });
    }
}
