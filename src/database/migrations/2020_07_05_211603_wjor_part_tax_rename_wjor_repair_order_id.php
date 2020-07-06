<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WjorPartTaxRenameWjorRepairOrderId extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('wjor_part_tax', function (Blueprint $table) {
			$table->renameColumn('wjor_repair_order_id', 'wjor_part_id');
			$table->dropForeign('wjor_part_tax_wjor_repair_order_id_foreign');
			$table->foreign('wjor_part_id')->references('id')->on('wjor_parts')->onDelete('CASCADE')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('wjor_part_tax', function (Blueprint $table) {
			$table->renameColumn('wjor_part_id', 'wjor_repair_order_id');
			$table->dropForeign('wjor_part_tax_wjor_part_id_foreign');
		});

	}
}
