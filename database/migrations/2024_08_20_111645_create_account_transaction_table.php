<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('terminal_id');
            $table->integer('account_head_id');
            $table->integer('other_account_head_id');
            $table->decimal('debit', 20, 2)->default(0);
            $table->decimal('credit', 20, 2)->default(0);
            $table->integer('document_id');
            $table->string('type');
            $table->text('narration')->nullable();
            $table->integer('receipt_id')->nullable();
            $table->string('posting_type')->nullable();
            $table->integer('posting_id')->nullable();
            $table->string('cheque')->nullable();
            $table->smallInteger('approved')->default(0);
            $table->integer('approved_by')->nullable();
            $table->integer('parent_account_id')->nullable();
            $table->integer('account_id')->nullable();
            $table->integer('parent_group_id')->nullable();
            $table->integer('group_id')->nullable();
            $table->timestamp('time');
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
        Schema::dropIfExists('account_transactions');
    }
};
