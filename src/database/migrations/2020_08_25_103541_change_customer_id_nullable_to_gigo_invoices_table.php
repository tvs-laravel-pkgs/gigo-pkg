<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCustomerIdNullableToGigoInvoicesTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gigo_invoices', function (Blueprint $table) {
			$table->unsignedInteger('customer_id')->nullable()->change();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gigo_invoices', function (Blueprint $table) {
			$table->unsignedInteger('customer_id')->change();
		});
	}
}
