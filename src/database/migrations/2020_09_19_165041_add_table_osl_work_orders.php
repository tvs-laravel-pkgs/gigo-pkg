<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableOslWorkOrders extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('osl_work_orders', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id');
			$table->string('number', 20);
			$table->unsignedInteger('job_card_id');
			$table->unsignedInteger('vendor_id');
			$table->text('work_order_description');
			$table->string('vendor_contact_no', 10);
			$table->string('invoice_number', 40)->nullable();
			$table->date('invoice_date')->nullable();
			$table->unsignedDecimal('invoice_amount')->nullable();
			$table->unsignedInteger('created_by_id');
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->timestamps();
			$table->softDeletes();
			$table->unique(['company_id', 'number']);

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('job_card_id')->references('id')->on('job_cards')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade')->onUpdate('cascade');

			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	public function down() {
		Schema::dropIfExists('osl_work_orders');
	}
}
