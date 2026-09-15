<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnvironmentToMobilePayments extends Migration
{
    public function up()
    {
        Schema::table('mobile_payments', function (Blueprint $table) {
            // Pre-existing payments must never be reinterpreted as sandbox transactions.
            $table->string('environment', 16)->default('live')->index();
        });
    }

    public function down()
    {
        Schema::table('mobile_payments', function (Blueprint $table) {
            $table->dropColumn('environment');
        });
    }
}
