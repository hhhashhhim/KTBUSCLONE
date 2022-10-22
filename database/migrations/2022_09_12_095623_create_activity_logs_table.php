<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('activity_logs', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('message');
        //     $table->integer('activity_by');
        //     $table->ipAddress('requested_host');
        //     $table->enum('status', ['Read', 'Unread']);
        //     $table->timestamp('time')->useCurrent();
        //     $table->softDeletes();
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
}
