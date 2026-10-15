<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCallCenterComplaintJourney extends Migration
{
    public function up()
    {
        Schema::table('call_center_calls', function (Blueprint $table) {
            $table->string('complaint_from_city', 120)->nullable();
            $table->string('complaint_to_city', 120)->nullable();
            $table->string('complaint_bus_time', 5)->nullable();
            $table->string('complaint_seat_number', 20)->nullable();
        });
    }

    public function down()
    {
        Schema::table('call_center_calls', function (Blueprint $table) {
            $table->dropColumn(['complaint_from_city', 'complaint_to_city', 'complaint_bus_time', 'complaint_seat_number']);
        });
    }
}
