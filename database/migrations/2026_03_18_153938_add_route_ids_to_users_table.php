<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRouteIdsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->longText('route_ids')->nullable()->after('destination_city_ids');
        });

        // aik bar ke liye sab routes ids nikal lo
        $allRouteIds = DB::table('routes')
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray();

        // existing sab users me default all routes save kar do
        DB::table('users')->update([
            'route_ids' => json_encode($allRouteIds)
        ]);
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
             $table->dropColumn('route_ids');
        });
    }
}
