<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrdersU876 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropForeign("job_orders_gate_log_id_foreign");
			$table->dropUnique('job_orders_company_id_gate_log_id_unique');
			$table->dropColumn("gate_log_id");

			$table->string('driver_name', 64)->nullable()->after("outlet_id");
			$table->string('driver_mobile_number', 10)->nullable()->after("driver_name");
			$table->unsignedInteger('vehicle_id')->after("number");
			$table->unsignedInteger('km_reading')->nullable()->after("vehicle_id");
			$table->unsignedInteger('km_reading_type_id')->nullable()->after("km_reading");

			$table->foreign("vehicle_id")->references("id")->on("vehicles")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->foreign("km_reading_type_id")->references("id")->on("configs")->onDelete("SET NULL")->onUpdate("SET NULL");

			$table->unsignedInteger('service_advisor_id')->nullable()->after('minimum_payable_amount');
			$table->foreign("service_advisor_id")->references("id")->on("employees")->onDelete("SET NULL")->onUpdate("CASCADE");
			$table->renameColumn('floor_advisor_id', 'floor_supervisor_id');

		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->unsignedInteger('gate_log_id')->after('company_id');
			$table->foreign("gate_log_id")->references("id")->on("gate_logs")->onDelete("CASCADE")->onUpdate("CASCADE");

			$table->dropForeign("job_orders_vehicle_id_foreign");
			$table->dropForeign("job_orders_km_reading_type_id_foreign");
			$table->dropForeign("job_orders_service_advisor_id_foreign");

			$table->dropColumn("driver_name");
			$table->dropColumn("driver_mobile_number");
			$table->dropColumn("vehicle_id");
			$table->dropColumn("km_reading");
			$table->dropColumn("km_reading_type_id");
			$table->dropColumn("service_advisor_id");
			$table->renameColumn('floor_supervisor_id', 'floor_advisor_id');

			$table->unique(["company_id", "gate_log_id"]);
		});
	}
}
