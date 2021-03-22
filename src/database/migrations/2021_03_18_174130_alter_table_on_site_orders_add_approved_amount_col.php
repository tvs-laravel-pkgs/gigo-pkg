<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableOnSiteOrdersAddApprovedAmountCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
		Schema::table('on_site_orders', function (Blueprint $table) {
			$table->unsignedDecimal('approved_amount',16,2)->nullable()->after('customer_approved_date_time');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('on_site_orders', function (Blueprint $table) {
			$table->dropColumn('approved_amount');
		});
	}
}
