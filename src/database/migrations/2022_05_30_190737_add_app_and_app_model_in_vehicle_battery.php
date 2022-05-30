<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAppAndAppModelInVehicleBattery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vehicle_batteries', function (Blueprint $table) {
            $table->unsignedInteger('application_id')->nullable()->after('remarks');
            $table->unsignedInteger('app_model_id')->nullable()->after('application_id');
            $table->foreign('application_id')->references('id')->on('battery_applications')->onDelete('SET NULL')->onUpdate('cascade');
            $table->foreign('app_model_id')->references('id')->on('models')->onDelete('SET NULL')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vehicle_batteries', function (Blueprint $table) {
            $table->dropForeign('vehicle_batteries_application_id_foreign');
            $table->dropColumn('application_id');

            $table->dropForeign('vehicle_batteries_app_model_id_foreign');
            $table->dropColumn('app_model_id');
        });
    }
}
