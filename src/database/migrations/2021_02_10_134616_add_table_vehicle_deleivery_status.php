<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableVehicleDeleiveryStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehicle_delivery_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->string('name', 100);

            $table->unique(["company_id", "name"]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vehicle_delivery_statuses');
    }
}
