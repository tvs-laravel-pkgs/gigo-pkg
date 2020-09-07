<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalTaxAmountToGigoInvoices extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gigo_invoices', function (Blueprint $table) {
			$table->unsignedDecimal('total_tax_amount', 12, 2)->nullable()->after('balance_amount');
		});
		Schema::table('gigo_invoice_items', function (Blueprint $table) {
			$table->unsignedInteger('grn_item_id')->nullable()->after('entity_id');
			$table->unsignedInteger('so_id')->nullable()->after('grn_item_id');
			$table->foreign('grn_item_id')->references('id')->on('grn_items')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('so_id')->references('id')->on('sale_orders')->onDelete('CASCADE')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gigo_invoices', function (Blueprint $table) {
			$table->dropColumn('total_tax_amount');
		});
		Schema::table('gigo_invoice_items', function (Blueprint $table) {
			$table->dropForeign('gigo_invoice_items_grn_item_id_foreign');
			$table->dropForeign('gigo_invoice_items_so_id_foreign');
			$table->dropColumn('grn_item_id');
			$table->dropColumn('so_id');
		});
	}
}
