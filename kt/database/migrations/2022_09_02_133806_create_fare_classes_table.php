<?php

use App\Models\FareClass;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFareClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fare_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(1);
            $table->integer('company_id')->nullable();
            $table->integer('added_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        $class = ['Economy','Business','Executive'];
        foreach ($class as $i => $singleClass) {
            FareClass::create(['name'=>$singleClass,'added_by'=>\Illuminate\Support\Facades\Auth::user()->id,'company_id'=>\Illuminate\Support\Facades\Auth::user()->company_id]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fare_classes');
    }
}
