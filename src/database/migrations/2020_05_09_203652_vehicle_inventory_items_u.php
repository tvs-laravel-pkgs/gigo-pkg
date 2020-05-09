<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VehicleInventoryItemsU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('vehicle_inventory_items', function (Blueprint $table) {
			$table->unsignedInteger('field_type_id')->nullable()->after('name');
			$table->foreign('field_type_id')->references('id')->on('field_types')->onDelete('set null')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('vehicle_inventory_items', function (Blueprint $table) {
			$table->dropForeign('vehicle_inventory_items_field_type_id_foreign');
			$table->dropColumn('field_type_id');
		});
	}
}
