<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableBatteryLoadTestResul extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
		Schema::table('battery_load_test_results', function (Blueprint $table) {
			$table->tinyInteger('is_battery_replaced')->after('overall_status_id')->nullable()->comment('1->Yes 0->No');
			$table->unsignedInteger('replaced_battery_make_id')->nullable()->after('is_battery_replaced');
			$table->string('replaced_battery_serial_number',40)->nullable()->after('replaced_battery_make_id');
			$table->tinyInteger('is_buy_back_opted')->after('replaced_battery_serial_number')->nullable()->comment('1->Yes 0->No');
			$table->unsignedInteger('battery_not_replaced_reason_id')->nullable()->after('is_buy_back_opted');
			$table->foreign('battery_not_replaced_reason_id','battery_not_replaced_reason_id_foreign')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('replaced_battery_make_id','replaced_battery_make_id_foreign')->references('id')->on('battery_makes')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('battery_load_test_results', function (Blueprint $table) {
			$table->dropForeign('replaced_battery_make_id_foreign');
			$table->dropForeign('battery_not_replaced_reason_id_foreign');
			$table->dropColumn('is_battery_replaced');
			$table->dropColumn('replaced_battery_make_id');
			$table->dropColumn('replaced_battery_serial_number');
			$table->dropColumn('is_buy_back_opted');
			$table->dropColumn('battery_not_replaced_reason_id');
		});
	}
}
