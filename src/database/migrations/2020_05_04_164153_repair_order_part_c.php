<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RepairOrderPartC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		if (!Schema::hasTable('repair_order_part')) {
			Schema::create('repair_order_part', function (Blueprint $table) {

				$table->unsignedInteger('repair_order_id');
				$table->unsignedInteger('part_id');

				$table->foreign("repair_order_id")->references("id")->on("repair_orders")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("part_id")->references("id")->on("parts")->onDelete("CASCADE")->onUpdate("CASCADE");

				$table->unique(['repair_order_id', 'part_id']);
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('repair_order_part');
	}
}
