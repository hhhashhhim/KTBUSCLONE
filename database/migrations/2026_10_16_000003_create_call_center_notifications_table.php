<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallCenterNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('call_center_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('recipient_id');
            $table->unsignedBigInteger('actor_id');
            $table->unsignedBigInteger('call_id')->index();
            $table->dateTime('created_at');
            $table->dateTime('read_at')->nullable();
            $table->dateTime('superseded_at')->nullable();
            $table->index(['company_id', 'recipient_id', 'superseded_at', 'read_at'], 'cc_notifications_inbox');
        });
    }

    public function down()
    {
        Schema::dropIfExists('call_center_notifications');
    }
}
