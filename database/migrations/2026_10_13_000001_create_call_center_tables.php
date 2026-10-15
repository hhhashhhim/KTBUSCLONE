<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallCenterTables extends Migration
{
    public function up()
    {
        Schema::create('call_center_calls', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('terminal_id');
            $table->unsignedBigInteger('agent_id');
            $table->date('call_date');
            $table->dateTime('called_at');
            $table->unsignedTinyInteger('shift');
            $table->string('category', 2);
            $table->string('status', 16)->default('new');
            $table->string('caller_name', 120)->nullable();
            $table->string('caller_phone', 25)->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->unsignedSmallInteger('seat_count')->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->dateTime('callback_at')->nullable();
            $table->unsignedBigInteger('parent_call_id')->nullable();
            $table->string('external_reference', 100)->nullable();
            $table->timestamps();
            $table->index(['company_id', 'terminal_id', 'call_date'], 'cc_company_terminal_date');
            $table->index(['company_id', 'agent_id', 'call_date'], 'cc_company_agent_date');
            $table->index(['company_id', 'assigned_to', 'status'], 'cc_assigned_status');
        });
        Schema::create('call_center_audits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('call_id')->index();
            $table->unsignedBigInteger('actor_id');
            $table->string('action', 20);
            $table->json('changes');
            $table->dateTime('created_at');
        });
        Schema::create('call_center_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('terminal_id')->default(0);
            $table->date('date');
            $table->unsignedTinyInteger('shift')->default(0);
            $table->text('remarks')->nullable();
            $table->text('next_day_actions')->nullable();
            $table->unsignedBigInteger('reviewed_by');
            $table->dateTime('reviewed_at');
            $table->string('snapshot_hash', 64);
            $table->unique(['company_id', 'terminal_id', 'date', 'shift'], 'cc_review_scope');
        });
    }

    public function down()
    {
        Schema::dropIfExists('call_center_reviews');
        Schema::dropIfExists('call_center_audits');
        Schema::dropIfExists('call_center_calls');
    }
}
