<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileFleetMediaTable extends Migration
{
    public function up()
    {
        Schema::create('mobile_fleet_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('bus_class_id')->nullable();
            $table->string('title', 150);
            $table->string('media_type', 30)->default('exterior');
            $table->string('image_url', 2048);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active', 'sort_order']);
            $table->index(['company_id', 'bus_class_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_fleet_media');
    }
}
