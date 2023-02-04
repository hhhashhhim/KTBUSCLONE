<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDropSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drop_schedules', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->nullable();
            $table->integer('terminal_id')->nullable();
            $table->date('date')->nullable();
            $table->date('schedule_date')->nullable();
            $table->integer('schedule_id')->nullable();
            $table->integer('added_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('is_drop')->nullable();
            $table->string('reason')->nullable();
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
        Schema::dropIfExists('drop_schedules');
    }
}
