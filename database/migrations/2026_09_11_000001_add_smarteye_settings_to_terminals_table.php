<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSmarteyeSettingsToTerminalsTable extends Migration
{
    public function up()
    {
        Schema::table('terminals', function (Blueprint $table) {
            $table->string('smarteye_email')->nullable();
            $table->string('smarteye_id')->nullable();
            $table->text('smarteye_password')->nullable();
            $table->text('smarteye_api')->nullable();
        });
    }

    public function down()
    {
        Schema::table('terminals', function (Blueprint $table) {
            $table->dropColumn([
                'smarteye_email',
                'smarteye_id',
                'smarteye_password',
                'smarteye_api',
            ]);
        });
    }
}
