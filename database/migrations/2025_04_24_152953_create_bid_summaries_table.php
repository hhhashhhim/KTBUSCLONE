<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBidSummariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bid_summaries', function (Blueprint $table) {
            $table->id();
            $table->integer('prn_id');
            $table->integer('mr_id');
            $table->string('status'); // 0= Rejected,  1 = Processing,  2 = Generated
            $table->integer('supplier_id');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->decimal('tax', 10, 2);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('advance', 10, 2);
            $table->decimal('advance_amount', 10, 2);
            $table->decimal('after_delivery', 10, 2);
            $table->decimal('after_delivery_amount', 10, 2);
            $table->integer('credit_days')->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('delivery_charges', 10, 2);
            $table->string('contact_person')->nullable();
            $table->string('contact_person_contact')->nullable();
            $table->text('terms_condition')->nullable();
            $table->string('quotation_date')->nullable(); 
            $table->string('quotation_ref')->nullable();  
            $table->string('added_by');
            $table->string('company_id');
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
        Schema::dropIfExists('bid_summaries');
    }
}
