<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAmcPolicyAddDiscountPercentage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
		Schema::table('amc_policies', function (Blueprint $table) {
			$table->unsignedDecimal('labour_discount_percentage',16,2)->nullable()->after('type');
			$table->unsignedDecimal('part_discount_percentage', 16, 2)->nullable()->after('labour_discount_percentage');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('amc_policies', function (Blueprint $table) {
			$table->dropColumn('labour_discount_percentage');
			$table->dropColumn('part_discount_percentage');
		});
	}
}
