<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrderRepairOrdersC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		if (!Schema::hasTable('job_order_repair_orders')) {
			Schema::create('job_order_repair_orders', function (Blueprint $table) {

				$table->increments('id');
				$table->unsignedInteger('job_order_id');
				$table->unsignedInteger('repair_order_id');
				$table->boolean('is_recommended_by_oem', 191)->nullable();
				$table->boolean('is_customer_approved')->nullable();
				$table->unsignedInteger('split_order_type_id')->nullable();
				$table->unsignedDecimal('qty', 12, 2);
				$table->unsignedDecimal('amount', 12, 2);
				$table->date('failure_date')->nullable();
				$table->unsignedInteger('status_id');
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("job_order_id")->references("id")->on("job_orders")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("repair_order_id")->references("id")->on("repair_orders")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("split_order_type_id")->references("id")->on("split_order_types")->onDelete("SET NULL")->onUpdate("SET NULL");
				$table->foreign("status_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->unique(["job_order_id", "repair_order_id"], 'joro_uk');

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('job_order_repair_orders');
	}
}
