<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GigoInvoiceC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('gigo_invoices', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id');
			$table->string('invoice_number', 191);
			$table->date('invoice_date');
			$table->unsignedInteger('customer_id');
			$table->unsignedInteger('invoice_of_id');
			$table->unsignedInteger('entity_id')->nullable();
			$table->unsignedInteger('outlet_id')->nullable();
			$table->integer('sbu_id')->nullable();
			$table->unsignedDecimal('invoice_amount', 12, 2);
			$table->unsignedDecimal('received_amount', 12, 2);
			$table->unsignedDecimal('balance_amount', 12, 2);
			$table->unsignedInteger('status_id');
			$table->unsignedInteger('created_by_id')->nullable();
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');

			$table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('invoice_of_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('sbu_id')->references('id')->on('sbus')->onDelete('SET NULL')->onUpdate('cascade');

			$table->foreign('status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');

			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');

			$table->unique(["invoice_number", "invoice_of_id"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('gigo_invoices');
	}
}
