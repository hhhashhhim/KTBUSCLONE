<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketMergeExpenses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_merge_expenses', function (Blueprint $table) {
            $table->id();
            $table->integer('ticket_merge_id');
            $table->integer('expense_category_id');
            $table->string('description');
            $table->decimal('amount',12,2);
            $table->string('invoice');
            $table->integer('added_by');
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
        Schema::dropIfExists('ticket_merge_expenses');
    }
}
