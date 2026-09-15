<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavedPassengersTable extends Migration
{
    public function up()
    {
        Schema::create('saved_passengers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('passenger_account_id');
            $table->unsignedBigInteger('company_id');
            $table->string('full_name', 150);
            $table->string('cnic', 13);
            $table->string('mobile', 20);
            $table->string('gender', 20);
            $table->timestamps();

            $table->unique(['passenger_account_id', 'cnic']);
            $table->index(['passenger_account_id', 'company_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('saved_passengers');
    }
}
