<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaintenanceDaysToMaintenancePartLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maintenance_part_links', function (Blueprint $table) {
              $table->integer('maintenance_days')->nullable()->after('maintenance_at');
              $table->date('maintenance_days_date')->nullable()->after('maintenance_days');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maintenance_part_links', function (Blueprint $table) {
            $table->dropColumn('maintenance_days');
        });
    }
}
