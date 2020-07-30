<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTbleJobOrdersAddStartEndDate extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropColumn('warranty_expiry_date');
			$table->date('amc_starting_date')->nullable()->after('ending_km');
			$table->date('amc_ending_date')->nullable()->after('amc_starting_date');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropColumn('amc_starting_date');
			$table->dropColumn('amc_ending_date');
			$table->date('warranty_expiry_date')->nullable()->after('ending_km');

		});
	}
}
