<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDockRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dock_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('fault_claim_id');
            $table->integer('bus_id')->nullable();
            $table->string('dock_time');
            $table->string('periority');
            $table->timestamp('dock_start_time');
            $table->integer('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('description')->nullable();
            $table->text('comments')->nullable();
            $table->string('status');
            $table->integer('added_by')->nullable();
            $table->integer('company_id')->nullable();
            $table->timestamp('time')->useCurrent();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dock_requests');
    }
}
