<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSplitOrderTypeInWarrantyJobOrders extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('warranty_job_order_requests', function (Blueprint $table) {
			$table->unsignedInteger('split_order_type_id')->nullable()->after('request_type_id');
			$table->foreign('split_order_type_id')->references('id')->on('split_order_types')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('warranty_job_order_requests', function (Blueprint $table) {
			$table->dropForeign('warranty_job_order_requests_split_order_type_id_foreign');
			$table->dropColumn('split_order_type_id');
		});
	}
}
