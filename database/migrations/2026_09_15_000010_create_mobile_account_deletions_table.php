<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileAccountDeletionsTable extends Migration
{
    public function up()
    {
        Schema::create('mobile_account_deletions', function (Blueprint $table) {
            $table->id();
            // Deliberately no FK: the passenger account is physically deleted.
            $table->unsignedBigInteger('passenger_account_id')->unique();
            $table->unsignedBigInteger('company_id')->index();
            $table->timestamp('deleted_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_account_deletions');
    }
}
