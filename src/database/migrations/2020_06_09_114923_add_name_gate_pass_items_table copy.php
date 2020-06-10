<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddNameGatePassItemsTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table("gate_pass_items", function ($table) {
			$table->string("name", 191)->after('gate_pass_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table("gate_pass_items", function ($table) {
			$table->dropColumn('name');
		});
	}
}
