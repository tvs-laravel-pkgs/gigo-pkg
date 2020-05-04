<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrderPartTaxC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		if (!Schema::hasTable('job_order_part_tax')) {
			Schema::create('job_order_part_tax', function (Blueprint $table) {

				$table->unsignedInteger('job_order_part_id');
				$table->unsignedInteger('tax_id');
				$table->unsignedDecimal('percentage', 5, 2);
				$table->unsignedDecimal('amount', 12, 2);

				$table->foreign("job_order_part_id")->references("id")->on("job_order_parts")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("tax_id")->references("id")->on("taxes")->onDelete("CASCADE")->onUpdate("CASCADE");

				$table->unique(["job_order_part_id", "tax_id"]);

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('job_order_part_tax');
	}
}
