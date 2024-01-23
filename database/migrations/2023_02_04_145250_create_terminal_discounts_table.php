<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminalDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('terminal_discounts', function (Blueprint $table) {
            $table->id();
            $table->integer('terminal_id');
            $table->integer('route_id');
            $table->decimal('discount',12,2);
            $table->date('start_date',12,2);
            $table->date('end_date',12,2);
            $table->integer('company_id')->nullable();
            $table->integer('added_by');
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
        Schema::dropIfExists('terminal_discounts');
    }
}
