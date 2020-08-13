<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTblTradePlateNumberAddStatusCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('trade_plate_numbers', function (Blueprint $table) {
			$table->unsignedInteger('status_id')->nullable()->after('insurance_validity_to');
			$table->foreign('status_id')->references('id')->on('configs')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('trade_plate_numbers', function (Blueprint $table) {
			$table->dropForeign('trade_plate_numbers_status_id_foreign');
			$table->dropColumn('status_id');
		});
	}
}
