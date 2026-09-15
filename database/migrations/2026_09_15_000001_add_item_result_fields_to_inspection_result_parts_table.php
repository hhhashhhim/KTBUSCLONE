<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddItemResultFieldsToInspectionResultPartsTable extends Migration
{
    public function up()
    {
        Schema::table('inspection_result_parts', function (Blueprint $table) {
            $table->unsignedBigInteger('fault_claim_part_id')->nullable()->after('fault_claim_id');
            $table->unsignedBigInteger('dock_request_id')->nullable()->after('inspection_result_id');
            $table->string('status')->nullable()->after('part_id');
            $table->string('repair_type')->nullable()->after('status');

            $table->index('fault_claim_part_id');
            $table->index('dock_request_id');
        });
    }

    public function down()
    {
        Schema::table('inspection_result_parts', function (Blueprint $table) {
            $table->dropIndex(['fault_claim_part_id']);
            $table->dropIndex(['dock_request_id']);
            $table->dropColumn([
                'fault_claim_part_id',
                'dock_request_id',
                'status',
                'repair_type',
            ]);
        });
    }
}
