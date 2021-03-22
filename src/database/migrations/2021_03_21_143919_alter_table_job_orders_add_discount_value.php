<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobOrdersAddDiscountValue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->unsignedDecimal('labour_discount_amount',16,2)->nullable()->after('warranty_reason');
			$table->unsignedDecimal('part_discount_amount', 16, 2)->nullable()->after('labour_discount_amount');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropColumn('labour_discount_amount');
			$table->dropColumn('part_discount_amount');
		});
	}
}
