<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GatePassesU1 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gate_passes', function (Blueprint $table) {
			$table->unsignedInteger('job_card_id')->nullable()->after('status_id');

			$table->foreign("job_card_id")->references("id")->on("job_cards")->onDelete("CASCADE")->onUpdate("CASCADE");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gate_passes', function (Blueprint $table) {
			$table->dropForeign('gate_passes_job_card_id_foreign');
			$table->dropColumn('job_card_id');
		});
	}
}
