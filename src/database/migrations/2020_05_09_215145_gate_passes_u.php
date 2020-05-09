<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GatePassesU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gate_passes', function (Blueprint $table) {
			$table->string('otp_no', 25)->nullable()->after('status_id');
			$table->datetime('gate_in_date')->nullable()->after('otp_no');
			$table->datetime('gate_out_date')->nullable()->after('gate_in_date');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gate_passes', function (Blueprint $table) {
			$table->dropColumn('otp_no');
			$table->dropColumn('gate_in_date');
			$table->dropColumn('gate_out_date');
		});
	}
}
