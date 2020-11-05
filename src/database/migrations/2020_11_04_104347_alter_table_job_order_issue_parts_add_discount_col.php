<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableJobOrderIssuePartsAddDiscountCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_order_issued_parts', function (Blueprint $table) {
			$table->unsignedInteger('discount_type')->nullable()->after('issued_mode_id');
			$table->unsignedDecimal('discount_value', 16, 2)->nullable()->after('discount_type');
			$table->unsignedDecimal('amount', 16, 2)->nullable()->after('discount_value');

			$table->foreign('discount_type')->references('id')->on('configs')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_order_issued_parts', function (Blueprint $table) {
			$table->dropForeign('job_order_issued_parts_discount_type_foreign');

			$table->dropColumn('discount_type');
			$table->dropColumn('discount_value');
			$table->dropColumn('amount');
		});
	}
}
