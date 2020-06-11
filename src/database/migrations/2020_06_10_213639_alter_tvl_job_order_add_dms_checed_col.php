<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTvlJobOrderAddDmsChecedCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->boolean('is_dms_verified')->nullable()->after('ewp_expiry_date')->comment('1 => Yes, 2 => No');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropColumn('is_dms_verified');
		});
	}
}
