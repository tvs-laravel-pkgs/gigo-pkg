<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTablePartsStockChangeColStock extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('part_stocks', function (Blueprint $table) {
			$table->unsignedDecimal('stock', 12, 2)->default(0)->change();
			$table->unsignedDecimal('cost_price', 12, 2)->default(0)->change();
			$table->unsignedDecimal('mrp', 12, 2)->default(0)->change();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('part_stocks', function (Blueprint $table) {
			$table->unsignedDecimal('stock', 12, 2)->default(NULL)->change();
			$table->unsignedDecimal('cost_price', 12, 2)->default(NULL)->change();
			$table->unsignedDecimal('mrp', 12, 2)->default(NULL)->change();
		});
	}
}
