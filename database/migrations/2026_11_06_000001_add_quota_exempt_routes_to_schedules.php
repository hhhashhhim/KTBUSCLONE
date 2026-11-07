<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuotaExemptRoutesToSchedules extends Migration
{
    public function up()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->json('quota_exempt_routes')->nullable()->after('intermediate_quota');
        });
    }

    public function down()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('quota_exempt_routes');
        });
    }
}
