<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CApplicationBatteryDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('application_battery_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('application_id')->nullable();
            $table->unsignedInteger('model_id')->nullable();
            $table->unsignedInteger('make_id')->nullable();
            $table->unsignedInteger('no_of_battery')->nullable();
            $table->unsignedInteger('created_by_id');
            $table->unsignedInteger('updated_by_id')->nullable();
            $table->unsignedInteger('deleted_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('application_id')->references('id')->on('battery_applications')->onDelete('SET NULL')->onUpdate('cascade');
            $table->foreign('model_id')->references('id')->on('models')->onDelete('SET NULL')->onUpdate('cascade');
            $table->foreign('make_id')->references('id')->on('vehicle_makes')->onDelete('SET NULL')->onUpdate('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('application_battery_details');
    }
}
