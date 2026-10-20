<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMobileMediaPlacements extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('mobile_fleet_media')) {
            throw new \RuntimeException('Install the mobile backend fleet-media table before Mobile App image placements.');
        }
        if (!Schema::hasColumn('mobile_fleet_media', 'placements')) {
            Schema::table('mobile_fleet_media', function (Blueprint $table) { $table->json('placements')->nullable(); });
        }
        if (!Schema::hasColumn('mobile_fleet_media', 'image_path')) {
            Schema::table('mobile_fleet_media', function (Blueprint $table) { $table->string('image_path', 255)->nullable(); });
        }
    }

    public function down()
    {
        Schema::table('mobile_fleet_media', function (Blueprint $table) { $table->dropColumn(['placements', 'image_path']); });
    }
}
