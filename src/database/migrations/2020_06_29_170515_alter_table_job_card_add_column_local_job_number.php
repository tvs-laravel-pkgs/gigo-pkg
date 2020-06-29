<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableJobCardAddColumnLocalJobNumber extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_cards', function (Blueprint $table) {
			$table->string('local_job_card_number', 40)->nullable()->after('job_card_number');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_cards', function (Blueprint $table) {
			$table->dropColumn('local_job_card_number');
		});
	}
}
