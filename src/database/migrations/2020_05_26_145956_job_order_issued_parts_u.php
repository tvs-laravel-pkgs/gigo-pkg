<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrderIssuedPartsU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_order_issued_parts', function (Blueprint $table) {
			$table->unsignedInteger('issued_mode_id')->nullable()->after("issued_to_id");
			$table->foreign("issued_mode_id")->references("id")->on("configs")->onDelete("SET NULL")->onUpdate("CASCADE");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_order_issued_parts', function (Blueprint $table) {
			$table->dropForeign("job_order_issued_parts_issued_mode_id_foreign");
			$table->dropColumn("issued_mode_id");
		});
	}
}
