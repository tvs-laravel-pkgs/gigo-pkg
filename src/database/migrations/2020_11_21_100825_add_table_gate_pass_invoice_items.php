<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableGatePassInvoiceItems extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('gate_pass_invoice_items', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('gate_pass_id');
			$table->unsignedInteger('category_id');
			$table->unsignedInteger('entity_id')->nullable();
			$table->string('entity_name', 155)->nullable();
			$table->string('entity_description', 155)->nullable();
			$table->unsignedDecimal('issue_qty', 16, 2);
			$table->unsignedDecimal('returned_qty', 16, 2);
			$table->unsignedInteger('status_id');
			$table->unsignedInteger('created_by_id');
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->timestamps();
			$table->foreign('gate_pass_id')->references('id')->on('gate_passes')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('category_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('entity_id')->references('id')->on('parts')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('gate_pass_invoice_items');
	}
}
