<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableVehicleBatteries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vehicle_batteries', function (Blueprint $table) {
            $table->unsignedInteger('outlet_id')->nullable()->after('business_id');
            $table->unsignedInteger('second_battery_make_id')->nullable()->after('battery_serial_number');
            $table->date('second_battery_manufactured_date')->nullable()->after('second_battery_make_id');
            $table->string('second_battery_serial_number', 40)->nullable()->after('second_battery_manufactured_date');
            $table->string('job_card_number', 30)->nullable()->after('second_battery_serial_number');
            $table->date('job_card_date')->nullable()->after('job_card_number');

            $table->string('invoice_number', 30)->nullable()->after('job_card_date');
            $table->date('invoice_date')->nullable()->after('invoice_number');
            $table->unsignedDecimal('invoice_amount', 10, 2)->nullable()->after('invoice_date');
            $table->unsignedInteger('battery_status_id')->nullable()->after('invoice_amount');
            $table->string('remarks', 400)->nullable()->after('battery_status_id');

            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('battery_status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('second_battery_make_id', 'vehicle_batteries_second_battery_id_foreign')->references('id')->on('battery_makes')->onDelete('cascade')->onUpdate('cascade');
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
            $table->dropForeign('vehicle_batteries_outlet_id_foreign');
            $table->dropForeign('vehicle_batteries_second_battery_id_foreign');
            $table->dropForeign('vehicle_batteries_battery_status_id_foreign');
            $table->dropColumn('outlet_id');
            $table->dropColumn('battery_status_id');
            $table->dropColumn('second_battery_make_id');
            $table->dropColumn('second_battery_manufactured_date');
            $table->dropColumn('second_battery_serial_number');
            $table->dropColumn('job_card_number');
            $table->dropColumn('job_card_date');
            $table->dropColumn('invoice_number');
            $table->dropColumn('invoice_date');
            $table->dropColumn('invoice_amount');
            $table->dropColumn('remarks');
        });
    }
}
