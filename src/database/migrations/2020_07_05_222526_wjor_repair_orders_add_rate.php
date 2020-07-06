<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WjorRepairOrdersAddRate extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('wjor_repair_orders', function (Blueprint $table) {
			$table->unsignedDecimal('rate', 12, 2)->default(0)->after('repair_order_id');
		});

		Schema::table('wjor_parts', function (Blueprint $table) {
			$table->unsignedDecimal('rate', 12, 2)->default(0)->after('quantity');
			$table->renameColumn('quantity', 'qty');
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('wjor_repair_orders', function (Blueprint $table) {
			$table->dropColumn('rate');
		});

		Schema::table('wjor_parts', function (Blueprint $table) {
			$table->renameColumn('qty', 'quanity');
			$table->dropColumn('rate');
		});
	}
}
