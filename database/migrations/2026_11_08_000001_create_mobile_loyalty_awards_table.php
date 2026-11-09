<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMobileLoyaltyAwardsTable extends Migration
{
    public function up()
    {
        Schema::create('mobile_loyalty_awards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('payment_id')->index();
            $table->unsignedBigInteger('invoice_id')->index();
            $table->unsignedBigInteger('ticket_id')->unique();
            $table->unsignedBigInteger('card_assign_id')->nullable();
            $table->unsignedBigInteger('card_category_id')->nullable();
            $table->string('point_type')->nullable();
            $table->decimal('point_value', 12, 2)->default(0);
            $table->decimal('booking_amount', 12, 2);
            $table->decimal('distance_in_km', 12, 2)->default(0);
            $table->unsignedInteger('earned_points')->default(0);
            $table->unsignedInteger('redeemed_points')->default(0);
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        // Card balances have already changed; dropping their reversal records
        // would make subsequent cancellations unsafe.
        if (Schema::hasTable('mobile_loyalty_awards') && DB::table('mobile_loyalty_awards')->exists()) {
            throw new RuntimeException('Reconcile mobile loyalty awards before removing their ledger.');
        }
        Schema::dropIfExists('mobile_loyalty_awards');
    }
}
