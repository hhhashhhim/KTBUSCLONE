<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('f_name')->nullable();
            $table->string('cnic')->nullable();
            $table->string('contact')->nullable();
            $table->text('address')->nullable();
            $table->string('reference')->nullable();
            $table->date('hiring_date')->nullable();
            $table->date('dob')->nullable();
            $table->integer('salary')->nullable();
            $table->enum('salary_type',['cash', 'bank'])->default('cash');
            $table->integer('working_days')->nullable();
            $table->integer('paid_leaves')->nullable();
            $table->string('blood_group', 10)->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('job_description')->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('designation_id')->nullable();
            $table->string('profile_Img')->nullable();
            $table->string('cnic_back_img')->nullable();
            $table->string('cnic_front_img')->nullable();
            $table->enum('status',['W', 'R', 'T'])->default('W');
            $table->integer('company_id')->nullable();
            $table->integer('added_by')->nullable();
            $table->timestamp('time')->useCurrent();
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
        Schema::dropIfExists('employees');
    }
}
