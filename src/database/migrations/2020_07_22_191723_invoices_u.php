<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InvoicesU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('invoices', function (Blueprint $table) {
			$table->unsignedInteger('payment_status_id')->nullable()->after('tax_amount');
			$table->foreign('payment_status_id')->references('id')->on('configs')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('invoices', function (Blueprint $table) {
			$table->dropForeign('invoices_payment_status_id_foreign');
			$table->dropColumn('payment_status_id');
		});
	}
}
