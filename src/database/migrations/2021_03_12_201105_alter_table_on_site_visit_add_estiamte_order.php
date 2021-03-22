<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableOnSiteVisitAddEstiamteOrder extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('on_site_order_repair_orders', function (Blueprint $table) {
			$table->unsignedInteger('estimate_order_id')->nullable()->after('split_order_type_id');
			$table->foreign('estimate_order_id', 'estimate_order_id_foreign')->references('id')->on('on_site_order_estimates')->onDelete('cascade')->onUpdate('cascade');
		});

		Schema::table('on_site_order_parts', function (Blueprint $table) {
			$table->unsignedInteger('estimate_order_id')->nullable()->after('split_order_type_id');
			$table->foreign('estimate_order_id', 'estimate_order_id')->references('id')->on('on_site_order_estimates')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('on_site_order_repair_orders', function (Blueprint $table) {
			$table->dropForeign('estimate_order_id_foreign');
			$table->dropColumn('estimate_order_id');
		});

		Schema::table('on_site_order_parts', function (Blueprint $table) {
			$table->dropForeign('estimate_order_id');
			$table->dropColumn('estimate_order_id');
		});
	}
}
