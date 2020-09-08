<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalTaxToGigoInvoiceItems extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gigo_invoice_items', function (Blueprint $table) {
			if (!Schema::hasColumn('gigo_invoice_items', 'total_tax_amount')) {
				$table->unsignedDecimal('total_tax_amount', 12, 2)->nullable()->after('amount');
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
			if (Schema::hasColumn('gigo_invoice_items', 'total_tax_amount')) {
				$table->dropColumn('total_tax_amount');
			}
		});
	}
}
