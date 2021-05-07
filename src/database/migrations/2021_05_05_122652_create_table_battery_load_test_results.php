<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableBatteryLoadTestResults extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('battery_load_test_results')) {
            Schema::create('battery_load_test_results', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('company_id');
                $table->unsignedInteger('outlet_id');
                $table->unsignedInteger('vehicle_battery_id');
                $table->string('amp_hour', 10);
                $table->string('battery_voltage', 20);
                $table->unsignedInteger('load_test_status_id');
                $table->unsignedInteger('hydrometer_electrolyte_status_id');
                $table->unsignedInteger('overall_status_id');
                $table->text('remarks');
                $table->unsignedInteger('created_by_id')->nullable();
                $table->unsignedInteger('updated_by_id')->nullable();
                $table->unsignedInteger('deleted_by_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('vehicle_battery_id')->references('id')->on('vehicle_batteries')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('load_test_status_id')->references('id')->on('load_test_statuses')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('hydrometer_electrolyte_status_id', 'hydrometer_electrolyte_status_id_foreign')->references('id')->on('hydrometer_electrolyte_statuses')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('overall_status_id')->references('id')->on('battery_load_test_statuses')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('battery_load_test_results');
    }
}
