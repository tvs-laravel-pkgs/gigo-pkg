<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableJobOrderAddAdvanceAmount extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->unsignedDecimal('advance_amount', 12, 2)->nullable()->after('floor_supervisor_id');
			$table->unsignedDecimal('advance_paid_amount', 12, 2)->nullable()->after('advance_amount');
			$table->unsignedInteger('advance_amount_status_id')->nullable()->after('advance_paid_amount');

			$table->foreign('advance_amount_status_id')->references('id')->on('configs')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropForeign('job_orders_advance_amount_status_id_foreign');

			$table->dropColumn('advance_amount');
			$table->dropColumn('advance_paid_amount');
			$table->dropColumn('advance_amount_status_id');
		});
	}
}
