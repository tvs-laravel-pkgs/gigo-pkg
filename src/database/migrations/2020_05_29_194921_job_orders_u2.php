<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrdersU2 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropForeign("job_orders_service_advisor_id_foreign");
			$table->foreign("service_advisor_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("CASCADE");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropForeign("job_orders_service_advisor_id_foreign");
			$table->foreign("service_advisor_id")->references("id")->on("employees")->onDelete("SET NULL")->onUpdate("CASCADE");
		});
	}
}
