<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrderPartsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		if (!Schema::hasTable('job_order_parts')) {
			Schema::create('job_order_parts', function (Blueprint $table) {

				$table->increments('id');
				$table->unsignedInteger('job_order_id');
				$table->unsignedInteger('part_id');
				$table->unsignedDecimal('qty', 12, 2);
				$table->unsignedInteger('split_order_type_id')->nullable();
				$table->unsignedDecimal('rate', 12, 2);
				$table->unsignedDecimal('amount', 12, 2);
				$table->unsignedInteger('status_id');
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("job_order_id")->references("id")->on("job_orders")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("part_id")->references("id")->on("parts")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("split_order_type_id")->references("id")->on("split_order_types")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("status_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('job_order_parts');
	}
}
