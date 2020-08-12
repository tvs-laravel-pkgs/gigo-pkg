<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveClaimAmountMaxClaimAmountFromRepairOrders extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('repair_orders', function (Blueprint $table) {
			$table->dropColumn('claim_amount');
			$table->dropColumn('maximum_claim_amount');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('repair_orders', function (Blueprint $table) {
			$table->decimal('claim_amount', 12, 2)->after('amount')->nullable();
			$table->decimal('maximum_claim_amount', 12, 2)->after('claim_amount')->nullable();
		});
	}
}
