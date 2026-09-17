<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobilePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('mobile_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('passenger_account_id')->index();
            $table->unsignedBigInteger('invoice_id')->unique();
            $table->unsignedBigInteger('quote_id')->unique();
            $table->string('method', 40);
            $table->string('transaction_reference', 40)->unique();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3)->default('PKR');
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedInteger('ticket_count');
            $table->unsignedBigInteger('wallet_card_id')->nullable();
            $table->unsignedInteger('wallet_points')->default(0);
            // A business deadline must never acquire MySQL's implicit ON UPDATE behavior.
            $table->dateTime('expires_at')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_payments');
    }
}
