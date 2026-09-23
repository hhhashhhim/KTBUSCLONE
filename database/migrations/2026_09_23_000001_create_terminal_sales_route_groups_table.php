<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminalSalesRouteGroupsTable extends Migration
{
    public function up()
    {
        Schema::create('terminal_sales_route_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('name', 120);
            $table->json('route_ids');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->unique(['company_id', 'name'], 'terminal_sales_route_group_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('terminal_sales_route_groups');
    }
}
