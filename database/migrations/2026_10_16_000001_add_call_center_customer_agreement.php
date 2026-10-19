<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCallCenterCustomerAgreement extends Migration
{
    public function up()
    {
        Schema::table('call_center_calls', function (Blueprint $table) {
            $table->string('customer_response', 16)->nullable();
            $table->unsignedSmallInteger('change_seat_count')->nullable();
            $table->decimal('proposed_refund_amount', 12, 2)->nullable();
            $table->date('proposed_travel_date')->nullable();
            $table->string('proposed_bus_time', 5)->nullable();
        });
    }

    public function down()
    {
        Schema::table('call_center_calls', function (Blueprint $table) {
            $table->dropColumn(['customer_response', 'change_seat_count', 'proposed_refund_amount', 'proposed_travel_date', 'proposed_bus_time']);
        });
    }
}
