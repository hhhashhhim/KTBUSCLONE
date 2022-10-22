<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminalCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('terminal_commissions', function (Blueprint $table) {
        //     $table->id();
        //     $table->integer('terminal_id');
        //     $table->integer('company_id')->nullable();
        //     $table->decimal('amount',12,2);
        //     $table->tinyInteger('per_seat')->default(0);
        //     $table->integer('added_by');
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
        Schema::dropIfExists('terminal_commissions');
    }
}
