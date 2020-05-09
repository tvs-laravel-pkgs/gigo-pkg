<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrderRepairOrdersU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_order_repair_orders', function (Blueprint $table) {
			$table->string('remarks', 191)->nullable()->after('status_id');
			$table->text('observation')->nullable()->after('remarks');
			$table->text('action_taken')->nullable()->after('observation');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_order_repair_orders', function (Blueprint $table) {
			$table->dropColumn('remarks');
			$table->dropColumn('observation');
			$table->dropColumn('action_taken');
		});
	}
}
