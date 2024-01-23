<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('discount_type')->comment("1/flat,2/percentage")->nullable();
            $table->decimal('flat_discount',12,2)->comment("per point")->nullable();
            $table->decimal('percentage_discount',12,2)->comment("per point")->nullable();
            $table->string('point_type')->comment("1/on amount,2/on distance")->nullable();
            $table->decimal('point_flat',12,2)->comment("rupee for 1 point")->nullable();
            $table->decimal('point_distance',12,2)->comment("km for 1 point")->nullable();
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
        Schema::dropIfExists('card_categories');
    }
}
