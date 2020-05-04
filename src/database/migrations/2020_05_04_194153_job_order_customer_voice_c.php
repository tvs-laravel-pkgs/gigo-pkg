<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrderCustomerVoiceC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		if (!Schema::hasTable('job_order_customer_voice')) {
			Schema::create('job_order_customer_voice', function (Blueprint $table) {

				$table->unsignedInteger('job_order_id');
				$table->unsignedInteger('customer_voice_id');

				$table->foreign("job_order_id")->references("id")->on("job_orders")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("customer_voice_id")->references("id")->on("customer_voices")->onDelete("CASCADE")->onUpdate("CASCADE");

				$table->unique(["job_order_id", "customer_voice_id"], 'jocv_uk');

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('job_order_customer_voice');
	}
}
