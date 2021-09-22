<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BatteryLoadTestResultsU1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('battery_load_test_results', function (Blueprint $table) {
            $table->unsignedInteger('battery_make_id')->nullable()->after('vehicle_battery_id');
            $table->unsignedTinyInteger('battery_type')->default(1)->after('battery_make_id');
            $table->date('manufactured_date')->nullable()->after('battery_type');
            $table->string('battery_serial_number', 40)->nullable()->after('manufactured_date');
            $table->text('remarks')->nullable()->change();

            $table->dropForeign('first_battery_amp_hour_id_foreign');
            $table->dropForeign('first_battery_battery_voltage_id_foreign');
            $table->dropForeign('second_battery_amp_hour_id_foreign');
            $table->dropForeign('battery_load_test_results_second_battery_part_id_foreign');
            $table->dropForeign('second_battery_battery_voltage_id_foreign');
            $table->dropForeign('second_battery_multimeter_test_status_id_foreign');
            $table->dropForeign('second_battery_load_test_status_id_foreign');
            $table->dropForeign('second_battery_hydrometer_electrolyte_status_id_foreign');
            $table->dropForeign('second_battery_overall_status_id_foreign');
            $table->dropForeign('replaced_second_battery_make_id_foreign');
            $table->dropForeign('second_battery_not_replaced_reason_id_foreign');



            $table->renameColumn('first_battery_amp_hour_id', 'battery_amp_hour_id');
            $table->renameColumn('first_battery_battery_voltage_id', 'battery_voltage_id');

            $table->dropColumn('second_battery_amp_hour_id');
            $table->dropColumn('second_battery_part_id');
            $table->dropColumn('second_battery_battery_voltage_id');
            $table->dropColumn('second_battery_multimeter_test_status_id');
            $table->dropColumn('second_battery_load_test_status_id');
            $table->dropColumn('second_battery_hydrometer_electrolyte_status_id');
            $table->dropColumn('second_battery_overall_status_id');
            $table->dropColumn('is_second_battery_replaced');
            $table->dropColumn('replaced_second_battery_make_id');
            $table->dropColumn('replaced_second_battery_serial_number');
            $table->dropColumn('is_second_battery_buy_back_opted');
            $table->dropColumn('second_battery_not_replaced_reason_id');
            $table->dropColumn('second_battery_remarks');
        
            $table->foreign('battery_make_id')->references('id')->on('battery_makes')->onDelete('SET NULL')->onUpdate('cascade');
            $table->foreign('battery_amp_hour_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('battery_voltage_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('battery_load_test_results', function (Blueprint $table) {
            $table->dropForeign('battery_load_test_results_battery_make_id_foreign');
            $table->dropColumn('battery_make_id');
            $table->dropColumn('battery_type');
            $table->dropColumn('manufactured_date');
            $table->dropColumn('battery_serial_number');

            $table->dropForeign('battery_load_test_results_battery_amp_hour_id_foreign');
            $table->dropForeign('battery_load_test_results_battery_voltage_id_foreign');
            $table->renameColumn('battery_amp_hour_id', 'first_battery_amp_hour_id');
            $table->renameColumn('battery_voltage_id', 'first_battery_battery_voltage_id');

            // $table->unsignedInteger('second_battery_amp_hour_id')->nullable()->after('overall_status_id');
            $table->unsignedInteger('second_battery_battery_voltage_id')->nullable()->after('second_battery_amp_hour_id');
            
            $table->unsignedInteger('second_battery_load_test_status_id')->nullable()->after('second_battery_battery_voltage_id');
            $table->unsignedInteger('second_battery_hydrometer_electrolyte_status_id')->nullable()->after('second_battery_load_test_status_id');
            $table->unsignedInteger('second_battery_multimeter_test_status_id')->nullable()->after('second_battery_battery_voltage_id');
            $table->unsignedInteger('second_battery_overall_status_id')->nullable()->after('second_battery_hydrometer_electrolyte_status_id');

            $table->tinyInteger('is_second_battery_replaced')->after('second_battery_overall_status_id')->nullable()->comment('1->Yes 0->No');
            $table->unsignedInteger('replaced_second_battery_make_id')->nullable()->after('is_second_battery_replaced');
            $table->string('replaced_second_battery_serial_number', 40)->nullable()->after('replaced_second_battery_make_id');
            $table->tinyInteger('is_second_battery_buy_back_opted')->after('replaced_second_battery_serial_number')->nullable()->comment('1->Yes 0->No');
            $table->unsignedInteger('second_battery_not_replaced_reason_id')->nullable()->after('is_second_battery_buy_back_opted');
            $table->unsignedInteger('second_battery_remarks')->nullable()->after('second_battery_not_replaced_reason_id');


             $table->foreign('second_battery_amp_hour_id', 'second_battery_amp_hour_id_foreign')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('second_battery_battery_voltage_id', 'second_battery_battery_voltage_id_foreign')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('second_battery_load_test_status_id', 'second_battery_load_test_status_id_foreign')->references('id')->on('load_test_statuses')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('second_battery_hydrometer_electrolyte_status_id', 'second_battery_hydrometer_electrolyte_status_id_foreign')->references('id')->on('hydrometer_electrolyte_statuses')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('second_battery_overall_status_id', 'second_battery_overall_status_id_foreign')->references('id')->on('battery_load_test_statuses')->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('second_battery_not_replaced_reason_id', 'second_battery_not_replaced_reason_id_foreign')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('replaced_second_battery_make_id', 'replaced_second_battery_make_id_foreign')->references('id')->on('battery_makes')->onDelete('cascade')->onUpdate('cascade');


            //Added
            $table->foreign('second_battery_multimeter_test_status_id','second_battery_multimeter_test_status_id_foreign')->references('id')->on('multimeter_test_statuses')->onDelete('cascade')->onUpdate('cascade');

        });
    }
}
