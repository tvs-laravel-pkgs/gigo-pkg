<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrderCampaignsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('job_order_campaigns')) {
			Schema::create('job_order_campaigns', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('job_order_id');
				$table->unsignedInteger('campaign_id');
				$table->string('authorisation_no', 64);
				$table->unsignedInteger('complaint_id')->nullable();
				$table->unsignedInteger('fault_id')->nullable();
				$table->unsignedInteger('claim_type_id')->nullable();
				$table->tinyInteger('campaign_type')->nullable();
				$table->unsignedInteger('vehicle_model_id')->nullable();
				$table->date('manufacture_date')->nullable();
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("job_order_id")->references("id")->on("job_orders")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("campaign_id")->references("id")->on("compaigns")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign('complaint_id')->references('id')->on('complaints')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('fault_id')->references('id')->on('faults')->onDelete('cascade')->onUpdate('cascade');

				$table->foreign("claim_type_id")->references("id")->on("configs")->onDelete("SET NULL")->onUpdate("CASCADE");
				$table->foreign('vehicle_model_id')->references('id')->on('models')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->unique(["job_order_id", "campaign_id"]);

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('job_order_campaigns');
	}
}
