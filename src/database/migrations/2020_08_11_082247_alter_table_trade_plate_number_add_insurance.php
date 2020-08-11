<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableTradePlateNumberAddInsurance extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('trade_plate_numbers', function (Blueprint $table) {
			$table->date('insurance_validity_from')->nullable()->after('trade_plate_number');
			$table->date('insurance_validity_to')->nullable()->after('insurance_validity_from');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('trade_plate_numbers', function (Blueprint $table) {
			$table->dropColumn('insurance_validity_from');
			$table->dropColumn('insurance_validity_to');
		});
	}
}
