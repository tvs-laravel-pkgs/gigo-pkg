<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MechanicTimeLogsU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('mechanic_time_logs', function (Blueprint $table) {
			$table->unsignedInteger('reason_id')->nullable()->after('status_id');

			$table->foreign("reason_id")->references("id")->on("pause_work_reasons")->onDelete("SET NULL")->onUpdate("CASCADE");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('mechanic_time_logs', function (Blueprint $table) {
			$table->dropForeign('mechanic_time_logs_reason_id_foreign');
			$table->dropColumn('reason_id');
		});
	}
}
