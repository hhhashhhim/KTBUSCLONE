<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCallCenterParentLookupIndex extends Migration
{
    public function up()
    {
        Schema::table('call_center_calls', function (Blueprint $table) {
            $table->index(['company_id', 'parent_call_id', 'category'], 'cc_parent_followups');
        });
    }

    public function down()
    {
        Schema::table('call_center_calls', function (Blueprint $table) {
            $table->dropIndex('cc_parent_followups');
        });
    }
}
