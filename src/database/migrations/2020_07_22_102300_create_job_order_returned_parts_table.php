<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobOrderReturnedPartsTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('job_order_returned_parts')) {
			Schema::create('job_order_returned_parts', function (Blueprint $table) {

				$table->increments('id');
				$table->unsignedInteger('job_order_part_id');
				$table->unsignedDecimal('returned_qty', 12, 2);
				$table->unsignedInteger('returned_to_id');
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("job_order_part_id")->references("id")->on("job_order_parts")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("returned_to_id")->references("id")->on("users")->onDelete("CASCADE")->onUpdate("CASCADE");
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
		Schema::dropIfExists('job_order_returned_parts');
	}
}
