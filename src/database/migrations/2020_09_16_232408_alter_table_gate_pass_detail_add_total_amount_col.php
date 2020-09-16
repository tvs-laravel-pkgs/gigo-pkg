<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableGatePassDetailAddTotalAmountCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gate_pass_details', function (Blueprint $table) {
			$table->unsignedDecimal('total_amount', 12, 2)->nullable()->after('invoice_amount');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gate_pass_details', function (Blueprint $table) {
			$table->dropColumn('total_amount');
		});
	}
}
