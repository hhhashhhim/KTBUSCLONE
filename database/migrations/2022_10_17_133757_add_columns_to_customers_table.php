<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('company_id')->after('contact')->nullable();
            $table->integer('added_by')->after('company_id')->nullable();
            $table->timestamp('time')->after('added_by')->useCurrent();
            $table->softDeletes()->after('time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('company_id');
            $table->dropColumn('added_by');
            $table->dropColumn('time');
            $table->dropColumn('deleted_at');
        });
    }
}
