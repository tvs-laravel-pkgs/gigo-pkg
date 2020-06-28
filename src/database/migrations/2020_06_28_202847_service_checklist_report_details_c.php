<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ServiceChecklistReportDetailsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('service_checklist_report_details')) {
			Schema::create('service_checklist_report_details', function (Blueprint $table) {
				$table->unsignedInteger('report_id');
				$table->unsignedInteger('service_check_list_id');
				$table->text('action_and_observation_taken');

				$table->foreign('report_id', 'scrd_report_foreign')->references('id')->on('service_checklist_reports')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('service_check_list_id', 'scrd_scl_foreign')->references('id')->on('service_checklists')->onDelete('CASCADE')->onUpdate('cascade');

				$table->unique(["report_id", "service_check_list_id"], 'scrd_unique');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('service_checklist_report_details');
	}
}
