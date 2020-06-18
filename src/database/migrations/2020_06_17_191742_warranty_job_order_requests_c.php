<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WarrantyJobOrderRequestsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('warranty_job_order_requests')) {
			Schema::create('warranty_job_order_requests', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('company_id');
				$table->string('number', 191);
				$table->unsignedInteger('job_order_id');
				$table->string('authorization_number', 191)->nullable();
				$table->date('failure_date');
				$table->boolean('has_warranty');
				$table->boolean('has_amc');
				$table->string('unit_serial_number', 32);
				$table->unsignedInteger('complaint_id')->nullable();
				$table->unsignedInteger('fault_id')->nullable();
				$table->unsignedInteger('supplier_id')->nullable();
				$table->unsignedInteger('primary_segment_id')->nullable();
				$table->unsignedInteger('secondary_segment_id')->nullable();
				$table->boolean('has_goodwill')->nullable();
				$table->unsignedInteger('operating_condition_id')->nullable();
				$table->unsignedInteger('normal_road_condition_id')->nullable();
				$table->unsignedInteger('failure_road_condition_id')->nullable();
				$table->unsignedInteger('load_carried_type_id')->nullable();
				$table->unsignedMediumInteger('load_carried')->nullable();
				$table->unsignedInteger('load_range_id')->nullable();
				$table->unsignedMediumInteger('load_at_failure')->nullable();
				$table->unsignedInteger('last_lube_changed')->nullable();
				$table->unsignedInteger('terrain_at_failure_id')->nullable();
				$table->unsignedInteger('reading_type_id')->nullable();
				$table->unsignedInteger('runs_per_day')->nullable();
				$table->unsignedInteger('failed_at')->nullable();
				$table->text('complaint_reported')->nullable();
				$table->text('failure_observed')->nullable();
				$table->text('investigation_findings')->nullable();
				$table->text('cause_of_failure')->nullable();
				$table->unsignedInteger('status_id');
				$table->unsignedInteger('created_by_id')->nullable();
				$table->unsignedInteger('updated_by_id')->nullable();
				$table->unsignedInteger('deleted_by_id')->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign('company_id')->references('id')->on('companies')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('complaint_id')->references('id')->on('complaints')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('fault_id')->references('id')->on('faults')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('supplier_id')->references('id')->on('part_suppliers')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('primary_segment_id')->references('id')->on('vehicle_primary_applications')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('secondary_segment_id')->references('id')->on('vehicle_secondary_applications')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('operating_condition_id')->references('id')->on('configs')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('normal_road_condition_id')->references('id')->on('configs')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('failure_road_condition_id')->references('id')->on('configs')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('load_carried_type_id')->references('id')->on('configs')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('load_range_id')->references('id')->on('configs')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('terrain_at_failure_id')->references('id')->on('configs')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('reading_type_id')->references('id')->on('configs')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('status_id')->references('id')->on('configs')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');

				$table->unique(["company_id", "number"]);
				$table->unique(["company_id", "authorization_number"], 'wjoran_unique');
			});
		} //
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('warranty_job_order_requests');
	}
}
