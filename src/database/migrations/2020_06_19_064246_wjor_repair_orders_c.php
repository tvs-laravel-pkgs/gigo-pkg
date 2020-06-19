<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WjorRepairOrdersC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('wjor_repair_orders')) {
			Schema::create('wjor_repair_orders', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('wjor_id');
				$table->unsignedInteger('repair_order_id');

				$table->foreign('wjor_id')->references('id')->on('warranty_job_order_requests')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('repair_order_id')->references('id')->on('repair_orders')->onDelete('CASCADE')->onUpdate('cascade');
			});
		} //
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('wjor_repair_orders');
	}
}
