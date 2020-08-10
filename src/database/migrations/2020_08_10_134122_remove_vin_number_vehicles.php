<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveVinNumberVehicles extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('vehicles', function (Blueprint $table) {
			$table->dropUnique('vehicles_company_id_vin_number_unique');
			$table->dropColumn('vin_number');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('vehicles', function (Blueprint $table) {
			$table->string('vin_number', 32)->after('registration_number')->nullable();
			$table->unique(["company_id", "vin_number"]);
		});
	}
}
