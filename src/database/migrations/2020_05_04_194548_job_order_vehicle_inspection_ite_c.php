<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrderVehicleInspectionIteC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		if (!Schema::hasTable('job_order_vehicle_inspection_item')) {
			Schema::create('job_order_vehicle_inspection_item', function (Blueprint $table) {

				$table->unsignedInteger('job_order_id');
				$table->unsignedInteger('vehicle_inspection_item_id');
				$table->unsignedInteger('status_id');

				$table->foreign("job_order_id", 'jovii_fk1')->references("id")->on("job_orders")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("vehicle_inspection_item_id", 'jovii_fk2')->references("id")->on("vehicle_inspection_items")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("status_id", 'jovii_fk3')->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");

				$table->unique(["job_order_id", "vehicle_inspection_item_id"], 'jovii_uni');

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('job_order_vehicle_inspection_ite');
	}
}
