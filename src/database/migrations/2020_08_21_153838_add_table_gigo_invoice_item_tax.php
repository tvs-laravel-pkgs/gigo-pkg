<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableGigoInvoiceItemTax extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('gigo_invoice_item_tax', function (Blueprint $table) {
			$table->unsignedInteger('invoice_item_id');
			$table->unsignedInteger('tax_id');
			$table->unsignedDecimal('percentage', 5, 2);
			$table->unsignedDecimal('amount', 12, 2);
			$table->foreign('invoice_item_id')->references('id')->on('gigo_invoice_items')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('tax_id')->references('id')->on('taxes')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('gigo_invoice_item_tax');
	}
}
