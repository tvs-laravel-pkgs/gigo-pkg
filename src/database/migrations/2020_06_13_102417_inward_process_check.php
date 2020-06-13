<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InwardProcessCheck extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('inward_process_check')) {
			Schema::create('inward_process_check', function (Blueprint $table) {

				$table->unsignedInteger('job_order_id');
				$table->unsignedInteger('tab_id');
				$table->boolean('is_form_filled');

				$table->foreign("job_order_id")->references("id")->on("job_orders")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("tab_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");

				$table->unique(['job_order_id', 'tab_id']);
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('inward_process_check');
	}
}
