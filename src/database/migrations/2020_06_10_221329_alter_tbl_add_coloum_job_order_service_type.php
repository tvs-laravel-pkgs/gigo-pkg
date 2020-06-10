<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTblAddColoumJobOrderServiceType extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_order_repair_orders', function (Blueprint $table) {
			$table->boolean('is_free_service')->nullable()->after('amount')->comment('1 => Yes, 0 => No');
		});
		Schema::table('job_order_parts', function (Blueprint $table) {
			$table->boolean('is_free_service')->nullable()->after('amount')->comment('1 => Yes, 0 => No');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_order_repair_orders', function (Blueprint $table) {
			$table->dropColumn('is_free_service');
		});
		Schema::table('job_order_parts', function (Blueprint $table) {
			$table->dropColumn('is_free_service');
		});
	}
}
