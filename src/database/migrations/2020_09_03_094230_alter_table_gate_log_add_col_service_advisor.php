<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableGateLogAddColServiceAdvisor extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gate_logs', function (Blueprint $table) {
			$table->unsignedInteger('service_advisor_id')->nullable()->after('outlet_id');
			$table->unsignedInteger('floor_supervisor_id')->nullable()->after('service_advisor_id');

			$table->foreign('service_advisor_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('floor_supervisor_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gate_logs', function (Blueprint $table) {
			$table->dropForeign('gate_logs_service_advisor_id_foreign');
			$table->dropForeign('gate_logs_floor_supervisor_id_foreign');

			$table->dropColumn('service_advisor_id');
			$table->dropColumn('floor_supervisor_id');
		});
	}
}
