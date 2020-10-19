<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableRoadTestAddObservations extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('road_test_gate_pass', function (Blueprint $table) {
			$table->unsignedInteger('trade_plate_number_id')->nullable()->after('gate_out_remarks');
			$table->unsignedInteger('road_test_done_by_id')->nullable()->after('trade_plate_number_id');
			$table->unsignedInteger('road_test_performed_by_id')->nullable()->after('road_test_done_by_id');
			$table->text('remarks')->nullable()->after('road_test_performed_by_id');
			$table->foreign('road_test_done_by_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('road_test_performed_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('trade_plate_number_id')->references('id')->on('trade_plate_numbers')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('road_test_gate_pass', function (Blueprint $table) {
			$table->dropForeign('road_test_gate_pass_trade_plate_number_id_foreign');
			$table->dropForeign('road_test_gate_pass_road_test_done_by_id_foreign');
			$table->dropForeign('road_test_gate_pass_road_test_performed_by_id_foreign');
			$table->dropColumn('road_test_done_by_id');
			$table->dropColumn('road_test_performed_by_id');
			$table->dropColumn('remarks');
			$table->dropColumn('trade_plate_number_id');
		});
	}
}
