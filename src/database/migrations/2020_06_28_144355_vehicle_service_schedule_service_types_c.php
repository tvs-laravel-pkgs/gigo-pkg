<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VehicleServiceScheduleServiceTypesC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('vehicle_service_schedule_service_types')) {
			Schema::create('vehicle_service_schedule_service_types', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('vehicle_service_schedule_id');
				$table->unsignedInteger('service_type_id');
				$table->boolean('is_free')->default(0);
				$table->unsignedInteger('km_reading');
				$table->unsignedMediumInteger('km_tolerance');
				$table->unsignedInteger('km_tolerance_type_id');
				$table->unsignedMediumInteger('period');
				$table->unsignedMediumInteger('period_tolerance');
				$table->unsignedInteger('period_tolerance_type_id');
				$table->unsignedInteger('created_by_id')->nullable();
				$table->unsignedInteger('updated_by_id')->nullable();
				$table->unsignedInteger('deleted_by_id')->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign('vehicle_service_schedule_id', 'vssst_vss_foriegn')->references('id')->on('vehicle_service_schedules')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('service_type_id', 'vssst_st_foriegn')->references('id')->on('service_types')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('km_tolerance_type_id', 'vssst_kmtt_foriegn')->references('id')->on('configs')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('period_tolerance_type_id', 'vssst_ptt_foriegn')->references('id')->on('configs')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('created_by_id', 'vssst_vcb_foriegn')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('updated_by_id', 'vssst_ub_foriegn')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('deleted_by_id', 'vssst_db_foriegn')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');

				$table->unique(["vehicle_service_schedule_id", "service_type_id"], 'vssst_unique');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('vehicle_service_schedule_service_types');
	}
}
