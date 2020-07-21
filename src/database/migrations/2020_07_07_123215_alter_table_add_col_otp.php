<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableAddColOtp extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->tinyInteger('is_customer_approved')->nullable()->after('is_customer_agreed');
			$table->string('otp_no', 25)->nullable()->after('is_customer_approved');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropColumn('is_customer_approved');
			$table->dropColumn('otp_no');
		});
	}
}
