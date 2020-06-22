<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RepairOrderAddCategoryId extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('repair_orders', function (Blueprint $table) {
			$table->unsignedInteger('category_id')->nullable()->after('name');
			$table->unsignedDecimal('maximum_claim_amount', 12, 2)->nullable()->after('claim_amount');

			$table->foreign('category_id')->references('id')->on('configs')->onDelete('SET NULL')->onUpdate('cascade');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('repair_orders', function (Blueprint $table) {
			$table->dropForeign('repair_orders_category_id_foreign');

			$table->dropColumn('category_id');
			$table->dropColumn('maximum_claim_amount');
		});
	}
}
