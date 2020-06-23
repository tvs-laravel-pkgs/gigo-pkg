<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrderCampaignPartC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('job_order_campaign_part')) {
			Schema::create('job_order_campaign_part', function (Blueprint $table) {
				$table->unsignedInteger('job_order_campaign_id');
				$table->unsignedInteger('part_id');

				$table->foreign("job_order_campaign_id")->references("id")->on("job_order_campaigns")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("part_id")->references("id")->on("parts")->onDelete("CASCADE")->onUpdate("CASCADE");

				$table->unique(['job_order_campaign_id', 'part_id']);
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('job_order_campaign_part');
	}
}
