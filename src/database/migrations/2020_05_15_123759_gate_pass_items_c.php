<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GatePassItemsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('gate_pass_items')) {
			Schema::create('gate_pass_items', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('gate_pass_id');
				$table->string('item_description', 191);
				$table->string('item_make', 191)->nullable();
				$table->string('item_model', 191)->nullable();
				$table->string('item_serial_no', 191)->nullable();
				$table->unsignedDecimal('qty', 12, 2);
				$table->string('remarks', 191)->nullable();
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("gate_pass_id")->references("id")->on("gate_passes")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->unique(["gate_pass_id", "item_serial_no"]);
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('gate_pass_items');
	}
}
