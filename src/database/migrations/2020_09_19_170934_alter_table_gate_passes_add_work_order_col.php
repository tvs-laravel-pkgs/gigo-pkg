<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableGatePassesAddWorkOrderCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gate_passes', function (Blueprint $table) {
			$table->unsignedInteger('gate_pass_of_id')->nullable()->after('job_card_id');
			$table->unsignedInteger('entity_id')->nullable()->after('gate_pass_of_id');

			$table->foreign('gate_pass_of_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gate_passes', function (Blueprint $table) {
			$table->dropForeign('gate_passes_gate_pass_of_id_foreign');

			$table->dropColumn('entity_id');
			$table->dropColumn('gate_pass_of_id');
		});
	}
}
