<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RepairOrderServiceTypeAddSplitOrderTypeId extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('repair_order_service_type', function (Blueprint $table) {
			$table->unsignedInteger('split_order_type_id')->after('schedule_id');
			$table->foreign('split_order_type_id')->references('id')->on('split_order_types')->onDelete('CASCADE')->onUpdate('cascade');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('repair_order_service_type', function (Blueprint $table) {
			$table->dropForeign('repair_order_service_type_split_order_type_id_foreign');
			$table->dropColumn('split_order_type_id');
		});
	}
}
