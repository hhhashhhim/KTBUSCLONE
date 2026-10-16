<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIntermediateQuotaToSchedules extends Migration
{
    public function up()
    {
        Schema::table('schedules', function (Blueprint $table) {
            // NULL preserves existing unrestricted booking; zero blocks advance paid partial journeys.
            $table->unsignedInteger('intermediate_quota')->nullable();
        });
    }

    public function down()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('intermediate_quota');
        });
    }
}
