<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableVehicleAddVehicleCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('vehicles', function (Blueprint $table) {
			$table->string('driver_name', 64)->nullable()->after('customer_id');
			$table->string('driver_mobile_number', 10)->nullable()->after('driver_name');
			$table->string('service_contact_number', 10)->nullable()->after('driver_mobile_number');
			$table->unsignedInteger('km_reading_type_id')->nullable()->after('service_contact_number');
			$table->unsignedInteger('km_reading')->nullable()->after('km_reading_type_id');
			$table->unsignedDecimal('hr_reading', 12, 2)->nullable()->after('km_reading');

			$table->foreign('km_reading_type_id')->references('id')->on('configs')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('vehicles', function (Blueprint $table) {
			$table->dropForeign('vehicles_km_reading_type_id_foreign');

			$table->dropColumn('driver_name');
			$table->dropColumn('driver_mobile_number');
			$table->dropColumn('service_contact_number');
			$table->dropColumn('km_reading_type_id');
			$table->dropColumn('km_reading');
			$table->dropColumn('hr_reading');
		});
	}
}
