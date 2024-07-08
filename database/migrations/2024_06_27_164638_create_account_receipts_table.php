<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_receipts', function (Blueprint $table) {
            $table->id();
            $table->integer('account_head_id');
            $table->integer('other_account_head_id');
            $table->integer('document_id');
            $table->string('type');
            $table->string('comment');
            $table->decimal('amount',15,2);
            $table->string('posting_type');
            $table->integer('posting_id');
            $table->integer('cheque_id');
            $table->integer('location_id');
            $table->integer('company_id');
            $table->integer('added_by');
            $table->integer('updated_by')->nullable();
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
        Schema::dropIfExists('account_receipts');
    }
}
