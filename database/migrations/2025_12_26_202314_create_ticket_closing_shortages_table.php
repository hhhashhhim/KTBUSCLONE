<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketClosingShortagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_closing_shortages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('terminal_id');

            $table->integer('passenger_count')->default(0);

            $table->decimal('kt_commission', 15, 2)->default(0);
            $table->decimal('other_commission', 15, 2)->default(0);

            $table->decimal('total_receivable', 15, 2)->default(0);

            $table->decimal('total_received_cash', 15, 2)->default(0);

            $table->unsignedBigInteger('bank_id')->nullable();
            $table->decimal('total_received_bank', 15, 2)->default(0);

            $table->decimal('shortage', 15, 2)->default(0);
            $table->decimal('received', 15, 2)->default(0);
            $table->string('type')->nullable();
            $table->unsignedBigInteger('ticket_closing_id');
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
        Schema::dropIfExists('ticket_closing_shortages');
    }
}
