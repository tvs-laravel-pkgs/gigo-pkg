<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestTypeIdWarrantyJobOrderRequests extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('warranty_job_order_requests', function (Blueprint $table) {
			$table->unsignedInteger('request_type_id')->after('rejected_reason')->nullable();
			$table->foreign('request_type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('warranty_job_order_requests', function (Blueprint $table) {
			$table->dropForeign('warranty_job_order_requests_request_type_id_foreign');
			$table->dropColumn('request_type_id');
		});
	}
}
