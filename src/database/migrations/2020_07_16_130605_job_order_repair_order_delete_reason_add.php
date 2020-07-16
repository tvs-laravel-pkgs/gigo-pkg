<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrderRepairOrderDeleteReasonAdd extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_order_repair_orders', function (Blueprint $table) {
			$table->unsignedInteger('removal_reason_id')->nullable()->after('action_taken');
			$table->string('removal_reason', 191)->nullable()->after('removal_reason_id');

			$table->foreign("removal_reason_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_order_repair_orders', function (Blueprint $table) {
			$table->dropForeign("job_order_repair_orders_removal_reason_id_foreign");
			$table->dropColumn('removal_reason_id');
			$table->dropColumn('removal_reason');
		});
	}
}
