<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableWarrantyJobOrderRequestAddColRationgRejectId extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('warranty_job_order_requests', function (Blueprint $table) {
			$table->tinyInteger('approval_rating')->nullable()->after('remarks');
			$table->unsignedInteger('ppr_reject_reason_id')->nullable()->after('approval_rating');
			$table->foreign('ppr_reject_reason_id')->references('id')->on('ppr_reject_reasons')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('warranty_job_order_requests', function (Blueprint $table) {
			$table->dropForeign('warranty_job_order_requests_ppr_reject_reason_id_foreign');

			$table->dropColumn('ppr_reject_reason_id');
			$table->dropColumn('approval_rating');
		});
	}
}
