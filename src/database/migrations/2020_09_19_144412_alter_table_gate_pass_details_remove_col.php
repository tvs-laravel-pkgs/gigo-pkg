<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableGatePassDetailsRemoveCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gate_pass_details', function (Blueprint $table) {
			$table->dropForeign('gate_pass_details_job_order_repair_order_id_foreign');

			$table->dropColumn('job_order_repair_order_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gate_pass_details', function (Blueprint $table) {
			$table->unsignedInteger('job_order_repair_order_id')->nullable()->after('vendor_contact_no');

			$table->foreign('job_order_repair_order_id')->references('id')->on('job_order_repair_orders')->onDelete('cascade')->onUpdate('cascade');
		});
	}
}
