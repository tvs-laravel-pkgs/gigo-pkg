<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrderPartsDeleteReasonAdd extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_order_parts', function (Blueprint $table) {
			$table->unsignedInteger('removal_reason_id')->nullable()->after('estimate_order_id');
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
		Schema::table('job_order_parts', function (Blueprint $table) {
			$table->dropForeign("job_order_parts_removal_reason_id_foreign");
			$table->dropColumn('removal_reason_id');
			$table->dropColumn('removal_reason');
		});
	}
}
