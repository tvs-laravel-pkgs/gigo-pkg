<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WjorTableAddNewFields extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('warranty_job_order_requests', function (Blueprint $table) {
			$table->text('remarks_for_not_changing_lube')->nullable()->after('last_lube_changed');
			$table->string('claim_number')->nullable()->after('split_order_type_id');
			$table->unsignedInteger('failure_type_id')->nullable()->after('claim_number');
			$table->foreign('failure_type_id')->references('id')->on('failure_types')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('warranty_job_order_requests', function (Blueprint $table) {
			$table->dropForeign('warranty_job_order_requests_failure_type_id_foreign');
			$table->dropColumn('remarks_for_not_changing_lube');
			$table->dropColumn('claim_number');
			$table->dropColumn('failure_type_id');
		});
	}
}
