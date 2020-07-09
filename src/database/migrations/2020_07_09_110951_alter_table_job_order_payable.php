<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableJobOrderPayable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_order_repair_orders', function (Blueprint $table) {
			$table->unsignedInteger('estimate_order_id')->default(0)->nullable()->after('is_customer_approved');
		});

		Schema::table('job_order_parts', function (Blueprint $table) {
			$table->tinyInteger('is_customer_approved')->default(0)->nullable()->after('is_oem_recommended');
			$table->unsignedInteger('estimate_order_id')->default(0)->nullable()->after('is_customer_approved');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_order_repair_orders', function (Blueprint $table) {
			$table->dropColumn('estimate_order_id');
		});

		Schema::table('job_order_parts', function (Blueprint $table) {
			$table->dropColumn('estimate_order_id');
			$table->dropColumn('is_customer_approved');
		});
	}
}
