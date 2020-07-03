<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WjorPartTaxC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('wjor_part_tax')) {
			Schema::create('wjor_part_tax', function (Blueprint $table) {
				$table->unsignedInteger('wjor_repair_order_id');
				$table->unsignedInteger('tax_id');
				$table->unsignedDecimal('percentage', 5, 2);
				$table->unsignedDecimal('amount', 10, 2);

				$table->foreign('wjor_repair_order_id')->references('id')->on('wjor_repair_orders')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('tax_id')->references('id')->on('taxes')->onDelete('CASCADE')->onUpdate('cascade');

				$table->unique(["wjor_repair_order_id", "tax_id"]);
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('wjor_part_tax');
	}
}
