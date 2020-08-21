<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableGigoInvoiceItem extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('gigo_invoice_items', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('invoice_id');
			$table->unsignedInteger('entity_type_id');
			$table->unsignedInteger('entity_id');
			$table->unsignedDecimal('qty', 8, 2);
			$table->unsignedDecimal('mrp', 12, 2);
			$table->unsignedDecimal('amount', 12, 2);
			$table->unsignedInteger('status_id');
			$table->unsignedInteger('created_by_id');
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->timestamps();
			$table->softDeletes();
			$table->foreign('invoice_id')->references('id')->on('gigo_invoices')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('entity_type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('gigo_invoice_items');
	}
}
