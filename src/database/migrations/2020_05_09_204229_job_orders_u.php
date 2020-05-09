<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrdersU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->text('expert_diagnosis_report')->nullable()->after('road_test_report');
			$table->unsignedInteger('expert_diagnosis_report_by_id')->nullable()->after('expert_diagnosis_report');
			$table->foreign("expert_diagnosis_report_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("SET NULL");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropColumn('expert_diagnosis_report');
			$table->dropForeign('job_orders_expert_diagnosis_report_by_id_foreign');
			$table->dropColumn('expert_diagnosis_report_by_id');
		});
	}
}
