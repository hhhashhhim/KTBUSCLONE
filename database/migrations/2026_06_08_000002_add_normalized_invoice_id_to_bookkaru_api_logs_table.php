<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNormalizedInvoiceIdToBookkaruApiLogsTable extends Migration
{
    public function up()
    {
        Schema::table('bookkaru_api_logs', function (Blueprint $table) {
            $table->string('normalized_invoice_id')->nullable()->after('invoice_id')->index();
        });
    }

    public function down()
    {
        Schema::table('bookkaru_api_logs', function (Blueprint $table) {
            $table->dropColumn('normalized_invoice_id');
        });
    }
}
