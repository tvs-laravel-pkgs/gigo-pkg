<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GateLogsU337 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::table('gate_logs', function (Blueprint $table) {
			$table->dropColumn("driver_name");
			$table->dropColumn("contact_number");
			$table->dropForeign("gate_logs_vehicle_id_foreign");
			$table->dropColumn("vehicle_id");
			$table->dropColumn("km_reading");
			$table->dropForeign("gate_logs_reading_type_id_foreign");
			$table->dropColumn("reading_type_id");
			$table->dropForeign("gate_logs_floor_adviser_id_foreign");
			$table->dropColumn("floor_adviser_id");

			$table->unsignedInteger('job_order_id')->after("company_id");

			$table->foreign("job_order_id")->references("id")->on("job_orders")->onDelete("CASCADE")->onUpdate("CASCADE");

		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gate_logs', function (Blueprint $table) {
			$table->string('driver_name', 64);
			$table->string('contact_number', 10)->nullable();
			$table->unsignedInteger('vehicle_id');
			$table->foreign("vehicle_id")->references("id")->on("vehicles")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->unsignedInteger('floor_adviser_id');
			$table->foreign("floor_adviser_id")->references("id")->on("employees")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->unsignedInteger('km_reading');
			$table->unsignedInteger('reading_type_id')->nullable();
			$table->foreign("reading_type_id")->references("id")->on("configs")->onDelete("SET NULL")->onUpdate("SET NULL");

			$table->dropForeign("gate_logs_job_order_id_foreign");

			$table->dropColumn("job_order_id");

		});
	}
}
