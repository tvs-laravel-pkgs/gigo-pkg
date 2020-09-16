<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableGatePassDetailAddInvoiceCols extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gate_pass_details', function (Blueprint $table) {
			$table->unsignedInteger('job_order_repair_order_id')->nullable()->after('vendor_contact_no');
			$table->string('invoice_number', 40)->nullable()->after('job_order_repair_order_id');
			$table->date('invoice_date')->nullable()->after('invoice_number');
			$table->unsignedDecimal('invoice_amount', 12, 2)->nullable()->after('invoice_date');

			$table->unique(['gate_pass_id', 'invoice_number']);
			$table->foreign('job_order_repair_order_id')->references('id')->on('job_order_repair_orders')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gate_pass_details', function (Blueprint $table) {

			$table->dropForeign('gate_pass_details_job_order_repair_order_id_foreign');

			$table->dropForeign('gate_pass_details_gate_pass_id_foreign');

			$table->dropUnique('gate_pass_details_gate_pass_id_invoice_number_unique');

			$table->foreign('gate_pass_id')->references('id')->on('gate_passes')->onDelete('cascade')->onUpdate('cascade');

			$table->dropColumn('invoice_number');
			$table->dropColumn('invoice_date');
			$table->dropColumn('invoice_amount');
			$table->dropColumn('job_order_repair_order_id');
		});
	}
}
