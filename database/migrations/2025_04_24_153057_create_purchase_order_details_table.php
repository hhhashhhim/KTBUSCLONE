<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_order_details', function (Blueprint $table) {
            $table->id();
            $table->integer('po_id');
            $table->integer('product_id');
            $table->decimal('qty', 10, 2);
            $table->decimal('rate', 10, 2);
            $table->decimal('sub_total', 10, 2);
            $table->decimal('tax'); // in percentage
            $table->decimal('tax_amount', 10, 2); //in amount
            $table->decimal('delivery', 10, 2);
            $table->decimal('discount', 10, 2);
            $table->decimal('net_amount', 10, 2);
            $table->boolean('gate_receive_note')->default(false);
            $table->boolean('store_received')->default(false);
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
        Schema::dropIfExists('purchase_order_details');
    }
}
