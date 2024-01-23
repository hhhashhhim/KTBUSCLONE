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
        Schema::create('terminal_commissions', function (Blueprint $table) {
            $table->id();
            $table->integer('terminal_id');
            $table->integer('route_id');
            $table->decimal('fix_commission',12,2);
            $table->decimal('flat_commission',12,2);
            $table->decimal('percentage_commission',12,2);
            $table->decimal('adjustment_commission',12,2);
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
        Schema::dropIfExists('terminal_commissions');
    }
}
