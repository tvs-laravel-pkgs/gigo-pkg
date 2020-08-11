<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrderReturnedPartsU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_order_returned_parts', function (Blueprint $table) {
			$table->string('remarks', 191)->nullable()->after('returned_to_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_order_returned_parts', function (Blueprint $table) {
			$table->dropColumn('remarks');
		});
	}
}
