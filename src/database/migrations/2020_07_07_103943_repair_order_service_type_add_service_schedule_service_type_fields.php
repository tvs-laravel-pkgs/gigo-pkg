<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RepairOrderServiceTypeAddServiceScheduleServiceTypeFields extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('repair_order_service_type', function (Blueprint $table) {
			$table->dropForeign('repair_order_service_type_repair_order_id_foreign');
			$table->dropForeign('repair_order_service_type_service_type_id_foreign');
			$table->dropUnique('repair_order_service_type_repair_order_id_service_type_id_unique');
			$table->dropColumn('service_type_id');
			$table->dropColumn('is_free_service');

			$table->unsignedInteger('schedule_id')->after('repair_order_id');
			$table->foreign('schedule_id')->references('id')->on('vehicle_service_schedule_service_types')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('repair_order_id')->references('id')->on('repair_orders')->onDelete('CASCADE')->onUpdate('cascade');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('repair_order_service_type', function (Blueprint $table) {
			$table->dropForeign('repair_order_service_type_schedule_id_foreign');
			$table->dropColumn('schedule_id');

			$table->unsignedInteger('service_type_id')->after('repair_order_id');
			$table->tinyInteger('is_free_service')->nullable()->after('service_type_id')->comment('1 => Yes, 0 => No');
			$table->foreign('service_type_id')->references('id')->on('service_types')->onDelete('CASCADE')->onUpdate('cascade');
			$table->unique(['repair_order_id', 'service_type_id']);
		});
	}
}
