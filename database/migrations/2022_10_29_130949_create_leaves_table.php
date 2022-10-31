<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->integer('applied_by')->nullable();
            $table->date('from')->nullable();
            $table->date('to')->nullable();
            $table->integer('days')->nullable();
            $table->integer('decider_id')->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', ['P', 'A', 'R', ])->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('added_by')->nullable();
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
        Schema::dropIfExists('leaves');
    }
}
