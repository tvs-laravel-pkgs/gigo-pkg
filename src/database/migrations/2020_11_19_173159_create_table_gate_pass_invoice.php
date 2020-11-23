<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableGatePassInvoice extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('gate_pass_invoices', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('gate_pass_id');
			$table->string('invoice_number', 20);
			$table->unsignedDecimal('invoice_amount', 16, 2);
			$table->date('invoice_date');
			$table->unsignedInteger('status_id')->nullable();
			$table->unsignedInteger('created_by_id');
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->timestamps();
			$table->unique(['invoice_number']);
			$table->foreign('gate_pass_id')->references('id')->on('gate_passes')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	public function down() {
		Schema::dropIfExists('gate_pass_invoices');
	}
}
