<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTblJobOrderAddColAmcStatus extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->tinyInteger('amc_status')->nullable()->after('expert_diagnosis_report_by_id');
			$table->unsignedInteger('starting_km')->nullable()->after('amc_status');
			$table->unsignedInteger('ending_km')->nullable()->after('starting_km');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropColumn('amc_status');
			$table->dropColumn('starting_km');
			$table->dropColumn('ending_km');
		});
	}
}
