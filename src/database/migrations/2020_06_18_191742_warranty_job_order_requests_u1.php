<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WarrantyJobOrderRequestsU1 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('warranty_job_order_requests', function (Blueprint $table) {
			$table->text('remarks')->nullable()->after('status_id');
			$table->text('rejected_reason')->nullable()->after('remarks');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('warranty_job_order_requests', function (Blueprint $table) {
			$table->dropColumn('remarks');
			$table->dropColumn('rejected_reason');
		});
	}
}
