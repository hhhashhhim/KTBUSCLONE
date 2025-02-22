<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToCustomerTabe extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('Customers', function (Blueprint $table) {
            $table->timestamp('loyalty_otp_expiration')->after('contact')->nullable();
            $table->integer('loyalty_otp')->after('contact')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('Customers', function (Blueprint $table) {
            $table->dropColumn("loyalty_otp");
            $table->dropColumn("loyalty_otp_expiration");
        });
    }
}
