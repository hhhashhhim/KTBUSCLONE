<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterBookkaruApiLogsInvoiceColumnsToInteger extends Migration
{
    public function up()
    {
        Schema::table('bookkaru_api_logs', function (Blueprint $table) {
            $table->integer('invoice_id')->nullable()->change();
            $table->integer('normalized_invoice_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('bookkaru_api_logs', function (Blueprint $table) {
            $table->string('invoice_id')->nullable()->change();
            $table->string('normalized_invoice_id')->nullable()->change();
        });
    }
}
