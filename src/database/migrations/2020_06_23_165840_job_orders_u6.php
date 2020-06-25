<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrdersU6 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->boolean('is_campaign_carried')->nullable()->after('ewp_expiry_date');
			$table->text('campaign_not_carried_remarks')->nullable()->after('is_campaign_carried');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropColumn('is_campaign_carried');
			$table->dropColumn('campaign_not_carried_remarks');
		});
	}
}
