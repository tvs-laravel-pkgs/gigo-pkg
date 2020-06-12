<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterVehicleSegmentAddVehicleMake extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::table('vehicle_segments', function (Blueprint $table) {
			$table->unsignedInteger('vehicle_make_id')->after('company_id')->nullable();
			$table->foreign("vehicle_make_id")->references("id")->on("vehicle_makes")->onDelete("SET NULL")->onUpdate("cascade");

			$table->dropForeign('vehicle_segments_company_id_foreign');

			$table->dropUnique('vehicle_segments_company_id_code_unique');
			$table->dropUnique('vehicle_segments_company_id_name_unique');

			$table->foreign("company_id")->references("id")->on("companies")->onDelete("cascade")->onUpdate("cascade");

			$table->unique(['company_id', 'vehicle_make_id', 'code']);
			$table->unique(['company_id', 'vehicle_make_id', 'name']);

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {

		Schema::table('vehicle_segments', function (Blueprint $table) {
			$table->dropForeign('vehicle_segments_vehicle_make_id_foreign');

			$table->dropForeign('vehicle_segments_company_id_foreign');

			$table->dropUnique('vehicle_segments_company_id_vehicle_make_id_code_unique');
			$table->dropUnique('vehicle_segments_company_id_vehicle_make_id_name_unique');

			$table->foreign("company_id")->references("id")->on("companies")->onDelete("cascade")->onUpdate("cascade");

			$table->dropColumn('vehicle_make_id');

			$table->unique(['company_id', 'code']);
			$table->unique(['company_id', 'name']);

		});
	}
}