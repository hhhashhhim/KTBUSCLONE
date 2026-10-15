<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCallCenterComplaintResolution extends Migration
{
    public function up()
    {
        Schema::table('call_center_calls', function (Blueprint $table) {
            $table->text('resolution_remarks')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
        });
    }

    public function down()
    {
        Schema::table('call_center_calls', function (Blueprint $table) {
            $table->dropColumn(['resolution_remarks', 'resolved_at', 'resolved_by']);
        });
    }
}
