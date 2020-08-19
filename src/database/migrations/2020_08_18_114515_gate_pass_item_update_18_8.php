<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GatePassItemUpdate188 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gate_pass_items', function (Blueprint $table) {
			$table->unsignedDecimal('return_qty', 12, 2)->after('qty')->nullable();
			$table->unsignedInteger('status_id')->after('return_qty')->nullable();

			$table->foreign('status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gate_pass_items', function (Blueprint $table) {
			$table->dropForeign("gate_pass_items_status_id_foreign");
			$table->dropColumn("return_qty");
			$table->dropColumn("status_id");
		});
	}
}
