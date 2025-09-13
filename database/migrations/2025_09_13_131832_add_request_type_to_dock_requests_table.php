<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestTypeToDockRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dock_requests', function (Blueprint $table) {
             $table->enum('request_type', ['regular', 'irregular'])
                  ->default('regular')
                  ->after('status'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dock_requests', function (Blueprint $table) {
           $table->dropColumn('request_type');
        });
    }
}
