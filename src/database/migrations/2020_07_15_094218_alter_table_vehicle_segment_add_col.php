<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableVehicleSegmentAddCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('vehicle_segments', function (Blueprint $table) {
			$table->unsignedInteger('vehicle_service_schedule_id')->nullable()->after('name');
			$table->foreign('vehicle_service_schedule_id')->references('id')->on('vehicle_service_schedules')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('vehicle_segments', function (Blueprint $table) {
			$table->dropForeign('vehicle_segments_vehicle_service_schedule_id_foreign');
			$table->dropColumn('vehicle_service_schedule_id');
		});
	}
}
