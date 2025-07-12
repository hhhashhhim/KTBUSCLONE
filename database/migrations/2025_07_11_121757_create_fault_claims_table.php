<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFaultClaimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fault_claims', function (Blueprint $table) {
            $table->id();
            $table->integer('bus_id');
            $table->integer('driver_id')->nullable();
            $table->text('description')->nullable();
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
        Schema::dropIfExists('fault_claims');
    }
}
