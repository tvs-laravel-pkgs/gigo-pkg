<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTbleGatePassEmployees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gate_pass_users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedinteger('gate_pass_id');
            $table->unsignedinteger('user_id');
            $table->dateTime('gate_out_date_time')->nullable();
            $table->dateTime('gate_in_date_time')->nullable();
            $table->unsignedInteger('created_by_id');
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->timestamps();

            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('gate_pass_id')->references('id')->on('gate_passes')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gate_pass_users');
    }
}
