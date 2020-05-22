<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GateLogsU481 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::table('gate_logs', function (Blueprint $table) {
			$table->dropForeign("gate_logs_status_id_foreign");

			$table->foreign("status_id")->references("id")->on("configs")->onDelete("SET NULL")->onUpdate("SET NULL");

		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gate_logs', function (Blueprint $table) {

		});
	}
}
