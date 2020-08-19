<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GatePassItemPivot extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('material_inward_logs', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedinteger('gass_pass_item_id');
			$table->unsignedDecimal('qty', 12, 2);
			$table->unsignedInteger('created_by_id')->nullable();
			$table->date('created_at');

			$table->foreign("gass_pass_item_id")->references("id")->on("gate_pass_items")->onDelete("CASCADE")->onUpdate("CASCADE");

			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('material_inward_logs');
	}
}
