<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WjorRepairOrdersAddAmountCols extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('wjor_repair_orders', function (Blueprint $table) {
			$table->unsignedDecimal('net_amount', 12, 2)->after('repair_order_id');
			$table->unsignedDecimal('tax_total', 12, 2)->after('net_amount');
			$table->unsignedDecimal('total_amount', 12, 2)->after('tax_total');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('wjor_repair_orders', function (Blueprint $table) {
			$table->dropColumn('net_amount');
			$table->dropColumn('tax_total');
			$table->dropColumn('total_amount');
		});
	}
}
