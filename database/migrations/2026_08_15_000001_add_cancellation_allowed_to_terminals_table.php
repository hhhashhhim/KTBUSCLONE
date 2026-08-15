<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terminals', function (Blueprint $table) {
            $table->boolean('cancellation_allowed')->default(false)->after('reservation_cancel');
        });
    }

    public function down(): void
    {
        Schema::table('terminals', function (Blueprint $table) {
            $table->dropColumn('cancellation_allowed');
        });
    }
};
