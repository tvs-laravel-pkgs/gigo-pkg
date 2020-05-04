<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrdersC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		if (!Schema::hasTable('job_orders')) {
			Schema::create('job_orders', function (Blueprint $table) {

				$table->increments('id');
				$table->unsignedInteger('company_id');
				$table->unsignedInteger('gate_log_id');
				$table->string('number', 191);
				$table->unsignedInteger('type_id')->nullable();
				$table->unsignedInteger('quote_type_id')->nullable();
				$table->unsignedInteger('service_type_id')->nullable();
				$table->unsignedInteger('outlet_id');
				$table->string('contact_number', 10)->nullable();
				$table->date('driver_license_expiry_date')->nullable();
				$table->date('insurance_expiry_date')->nullable();
				$table->text('voc')->nullable();
				$table->boolean('is_road_test_required')->nullable();
				$table->unsignedInteger('road_test_done_by_id')->nullable();
				$table->unsignedInteger('road_test_performed_by_id')->nullable();
				$table->text('road_test_report')->nullable();
				$table->date('warranty_expiry_date')->nullable();
				$table->date('ewp_expiry_date')->nullable();
				$table->unsignedInteger('status_id')->nullable();
				$table->datetime('estimated_delivery_date')->nullable();
				$table->unsignedInteger('estimation_type_id')->nullable();
				$table->unsignedDecimal('minimum_payable_amount', 12, 2)->nullable();
				$table->unsignedInteger('floor_advisor_id')->nullable();
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("company_id")->references("id")->on("companies")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("gate_log_id")->references("id")->on("gate_logs")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("type_id")->references("id")->on("service_order_types")->onDelete("SET NULL")->onUpdate("SET NULL");
				$table->foreign("quote_type_id")->references("id")->on("quote_types")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("service_type_id")->references("id")->on("service_types")->onDelete("SET NULL")->onUpdate("SET NULL");
				$table->foreign("outlet_id")->references("id")->on("outlets")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("road_test_done_by_id")->references("id")->on("configs")->onDelete("SET NULL")->onUpdate("SET NULL");
				$table->foreign("road_test_performed_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("SET NULL");
				$table->foreign("status_id")->references("id")->on("configs")->onDelete("SET NULL")->onUpdate("SET NULL");
				$table->foreign("estimation_type_id")->references("id")->on("estimation_types")->onDelete("SET NULL")->onUpdate("SET NULL");
				$table->foreign("floor_advisor_id")->references("id")->on("users")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->unique(["company_id", "gate_log_id"]);
				$table->unique(["company_id", "number"]);

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('job_orders');
	}
}
