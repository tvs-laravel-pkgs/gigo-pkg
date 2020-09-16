<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountFieldsToGigoInvoiceItemsTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gigo_invoice_items', function (Blueprint $table) {
			if (!Schema::hasColumn('gigo_invoice_items', 'discount_percentage')) {
				$table->unsignedDecimal('discount_percentage', 12, 2)->nullable()->after('mrp')->comment('For single quantity');
			}
			if (!Schema::hasColumn('gigo_invoice_items', 'discount_amount')) {
				$table->unsignedDecimal('discount_amount', 12, 2)->nullable()->after('discount_percentage')->comment('For single quantity');
			}
			if (!Schema::hasColumn('gigo_invoice_items', 'amount_after_discount')) {
				$table->unsignedDecimal('amount_after_discount', 12, 2)->nullable()->after('discount_amount')->comment('For single quantity');
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gigo_invoice_items', function (Blueprint $table) {
			$table->dropColumn('discount_percentage');
			$table->dropColumn('discount_amount');
			$table->dropColumn('amount_after_discount');
		});
	}
}
