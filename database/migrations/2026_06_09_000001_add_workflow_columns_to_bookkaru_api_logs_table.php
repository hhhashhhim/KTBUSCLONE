<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkflowColumnsToBookkaruApiLogsTable extends Migration
{
    public function up()
    {
        Schema::table('bookkaru_api_logs', function (Blueprint $table) {
            $table->string('approval_status')->nullable()->after('status')->index();
            $table->integer('approved_by')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->integer('rejected_by')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            $table->string('cancellation_status')->nullable()->after('rejection_reason')->index();
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_status');
        });
    }

    public function down()
    {
        Schema::table('bookkaru_api_logs', function (Blueprint $table) {
            $table->dropColumn([
                'approval_status',
                'approved_by',
                'approved_at',
                'rejected_by',
                'rejected_at',
                'rejection_reason',
                'cancellation_status',
                'cancelled_at',
            ]);
        });
    }
}
