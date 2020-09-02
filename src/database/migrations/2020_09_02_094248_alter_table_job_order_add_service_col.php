<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableJobOrderAddServiceCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->unsignedInteger('service_policy_id')->nullable()->after('expert_diagnosis_report_by_id');
			$table->foreign('service_policy_id')->references('id')->on('amc_members')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropForeign('job_orders_service_policy_id_foreign');
			$table->dropColumn('service_policy_id');
		});
	}
}
