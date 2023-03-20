<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardAssignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_assigns', function (Blueprint $table) {
            $table->id();
            $table->string('rfId')->nullable();
            $table->string('cnic')->nullable();
            $table->string('phone')->nullable();
            $table->string('name')->nullable();
            $table->integer('card_category_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('starting_points')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('added_by')->nullable();
            $table->integer('updated_by')->nullable();
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
        Schema::dropIfExists('card_assigns');
    }
}
