<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('uan')->nullable();
            $table->string('phone')->nullable();
            $table->integer('show_phone');
            $table->string('footer_text')->nullable();
            $table->integer('show_coupen');
            $table->string('address')->nullable();
            $table->text('terms_condition')->nullable();
            $table->string('status')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('added_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('time')->usecurrent();
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
        Schema::dropIfExists('tickets_templates');
    }
}
