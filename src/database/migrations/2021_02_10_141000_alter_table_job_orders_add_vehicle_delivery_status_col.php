<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobOrdersAddVehicleDeliveryStatusCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->unsignedInteger('vehicle_delivery_status_id')->nullable()->after('status_id');
            $table->foreign('vehicle_delivery_status_id')->references('id')->on('vehicle_delivery_statuses')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropForeign('job_orders_vehicle_delivery_status_id_foreign');
            $table->dropColumn('vehicle_delivery_status_id');
        });
    }
}
