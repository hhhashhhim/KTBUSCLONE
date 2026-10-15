<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCallCenterInquiryTopic extends Migration
{
    public function up()
    {
        Schema::table('call_center_calls', function (Blueprint $table) {
            $table->string('inquiry_topic', 16)->nullable();
        });
    }

    public function down()
    {
        Schema::table('call_center_calls', function (Blueprint $table) {
            $table->dropColumn('inquiry_topic');
        });
    }
}
