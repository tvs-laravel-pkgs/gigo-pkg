<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrderCampaignRepairOrderC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('job_order_campaign_repair_order')) {
			Schema::create('job_order_campaign_repair_order', function (Blueprint $table) {
				$table->unsignedInteger('job_order_campaign_id');
				$table->unsignedInteger('repair_order_id');
				$table->unsignedDecimal('amount', 12, 2);

				$table->foreign("job_order_campaign_id")->references("id")->on("job_order_campaigns")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("repair_order_id")->references("id")->on("repair_orders")->onDelete("CASCADE")->onUpdate("CASCADE");

				$table->unique(['job_order_campaign_id', 'repair_order_id'], 'jo_campaign_ro_fk');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('job_order_campaign_repair_order');
	}
}
