<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableGigoManulaInvoice extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('gigo_manual_invoices', function (Blueprint $table) {
			$table->increments('id');
			$table->string('number', 20);
			$table->date('invoice_date');
			$table->unsignedInteger('customer_id');
			$table->morphs('invoiceable');
			$table->unsignedInteger('invoice_type_id');
			$table->unsignedInteger('outlet_id')->nullable();
			$table->integer('sbu_id')->nullable();
			$table->unsignedDecimal('amount', 16, 2);
			$table->unsignedInteger('payment_status_id');
			$table->unsignedInteger('receipt_id')->nullable();
			$table->unsignedInteger('created_by_id');
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->timestamps();
			$table->softDeletes();
			$table->unique(['number']);
			$table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('invoice_type_id')->references('id')->on('invoice_types')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('sbu_id')->references('id')->on('sbus')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('payment_status_id')->references('id')->on('gigo_manual_invoice_payment_statuses')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('receipt_id')->references('id')->on('receipts')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	public function down() {
		Schema::dropIfExists('gigo_manual_invoices');
	}
}
