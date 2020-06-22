<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RepairOrdersU22 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('repair_orders', function (Blueprint $table) {
			$table->unsignedInteger('type_id')->nullable()->change();
			$table->unsignedDecimal('claim_amount', 12, 2)->nullable()->after('amount');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('repair_orders', function (Blueprint $table) {
			$table->unsignedInteger('type_id')->nullable(false)->change();
			$table->dropColumn('claim_amount');
		});
	}
}
