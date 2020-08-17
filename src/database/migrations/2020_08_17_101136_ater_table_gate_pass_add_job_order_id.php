<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AterTableGatePassAddJobOrderId extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('gate_passes', function (Blueprint $table) {
			$table->unsignedInteger('job_order_id')->nullable()->after('status_id');
			$table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('gate_passes', function (Blueprint $table) {
			$table->dropForeign('gate_passes_job_order_id_foreign');
			$table->dropColumn('job_order_id');
		});
	}
}
