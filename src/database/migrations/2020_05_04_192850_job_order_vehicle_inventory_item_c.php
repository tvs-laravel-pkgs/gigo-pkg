<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrderVehicleInventoryItemC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		if (!Schema::hasTable('job_order_vehicle_inventory_item')) {
			Schema::create('job_order_vehicle_inventory_item', function (Blueprint $table) {

				$table->unsignedInteger('job_order_id');
				$table->unsignedInteger('vehicle_inventory_item_id');
				$table->boolean('is_available');
				$table->text('remarks')->nullable();

				$table->foreign("job_order_id")->references("id")->on("job_orders")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("vehicle_inventory_item_id", 'jovii_item_fk')->references("id")->on("vehicle_inventory_items")->onDelete("CASCADE")->onUpdate("CASCADE");

				$table->unique(["job_order_id", "vehicle_inventory_item_id"], 'job_order_vehicle_inventory_item_u');

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('job_order_vehicle_inventory_item');
	}
}
