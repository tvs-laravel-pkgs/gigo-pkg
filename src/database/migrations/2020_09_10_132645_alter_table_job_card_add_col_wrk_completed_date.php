<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableJobCardAddColWrkCompletedDate extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_cards', function (Blueprint $table) {
			$table->dateTime('work_completed_at')->nullable()->after('local_job_card_number');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_cards', function (Blueprint $table) {
			$table->dropColumn('work_completed_at');
		});
	}
}
