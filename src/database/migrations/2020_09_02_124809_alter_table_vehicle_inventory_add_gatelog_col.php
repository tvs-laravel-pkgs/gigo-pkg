<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableVehicleInventoryAddGatelogCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_order_vehicle_inventory_item', function (Blueprint $table) {

			$table->unsignedInteger('gate_log_id')->nullable()->after('job_order_id');

			$table->dropForeign('job_order_vehicle_inventory_item_job_order_id_foreign');
			$table->dropForeign('jovii_item_fk');
			$table->dropUnique('job_order_vehicle_inventory_item_u');

			$table->foreign('gate_log_id')->references('id')->on('gate_logs')->onDelete('cascade')->onUpdate('cascade');

			$table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('vehicle_inventory_item_id', 'jovii_item_fk')->references('id')->on('vehicle_inventory_items')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(['job_order_id', 'gate_log_id', 'vehicle_inventory_item_id'], 'unique_job_order_gate_log_inventory_id');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_order_vehicle_inventory_item', function (Blueprint $table) {

			$table->dropForeign('job_order_vehicle_inventory_item_job_order_id_foreign');
			$table->dropForeign('jovii_item_fk');
			$table->dropForeign('job_order_vehicle_inventory_item_gate_log_id_foreign');
			$table->dropUnique('unique_job_order_gate_log_inventory_id');

			$table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('vehicle_inventory_item_id', 'jovii_item_fk')->references('id')->on('vehicle_inventory_items')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(['job_order_id', 'vehicle_inventory_item_id'], 'job_order_vehicle_inventory_item_u');

			$table->dropColumn('gate_log_id');
		});
	}
}
