<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableGigoManualInvoiceChangeUnique extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
		Schema::table('gigo_manual_invoices', function (Blueprint $table) {
            $table->dropUnique('gigo_manual_invoices_number_unique');
			$table->unique(["number", "invoice_type_id"],'gigo_manual_invoices_number_unique');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gigo_manual_invoices', function (Blueprint $table) {
            $table->dropUnique('gigo_manual_invoices_number_unique');
            $table->unique(["number"]);
		});
	}
}
