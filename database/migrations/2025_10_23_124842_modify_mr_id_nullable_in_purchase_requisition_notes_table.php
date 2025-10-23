<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyMrIdNullableInPurchaseRequisitionNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_requisition_notes', function (Blueprint $table) {
           $table->unsignedBigInteger('mr_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_requisition_notes', function (Blueprint $table) {
             $table->unsignedBigInteger('mr_id')->nullable(false)->change();
        });
    }
}
