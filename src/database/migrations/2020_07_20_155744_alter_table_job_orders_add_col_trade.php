<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableJobOrdersAddColTrade extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->unsignedInteger('trade_plate_number_id')->nullable()->after('vehicle_id');
			$table->foreign('trade_plate_number_id')->references('id')->on('trade_plate_numbers')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropForeign('job_orders_trade_plate_number_id_foreign');
			$table->dropColumn('trade_plate_number_id');
		});
	}
}
