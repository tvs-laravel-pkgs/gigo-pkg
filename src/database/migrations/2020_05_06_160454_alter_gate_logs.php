<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterGateLogs extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gate_logs', function (Blueprint $table) {
			$table->unsignedInteger('floor_adviser_id')->nullable()->after('gate_pass_id');

			$table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('set null')->onUpdate('cascade');
			$table->foreign('floor_adviser_id')->references('id')->on('employees')->onDelete('set null')->onUpdate('cascade');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gate_logs', function (Blueprint $table) {
			$table->dropForeign('gate_logs_floor_adviser_id_foreign');
			$table->dropForeign('gate_logs_vehicle_id_foreign');

			$table->dropColumn('floor_adviser_id');
		});
	}
}
