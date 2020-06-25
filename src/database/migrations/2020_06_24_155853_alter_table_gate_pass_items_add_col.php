<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableGatePassItemsAddCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gate_pass_items', function (Blueprint $table) {
			$table->dropForeign('gate_pass_items_gate_pass_id_foreign');
			$table->dropUnique('gate_pass_items_gate_pass_id_item_serial_no_unique');
			$table->foreign("gate_pass_id")->references("id")->on("gate_passes")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->unique(['gate_pass_id', 'item_serial_no', 'name']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gate_pass_items', function (Blueprint $table) {
			$table->dropForeign('gate_pass_items_gate_pass_id_foreign');
			$table->dropUnique('gate_pass_items_gate_pass_id_item_serial_no_name_unique');
			$table->foreign("gate_pass_id")->references("id")->on("gate_passes")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->unique(['gate_pass_id', 'item_serial_no']);
		});
	}
}
