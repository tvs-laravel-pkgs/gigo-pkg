<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GateLogsU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gate_logs', function (Blueprint $table) {
			$table->unsignedInteger('outlet_id')->nullable()->after('status_id');
			$table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('set null')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gate_logs', function (Blueprint $table) {
			$table->dropForeign('gate_logs_outlet_id_foreign');
			$table->dropColumn('outlet_id');
		});
	}
}
