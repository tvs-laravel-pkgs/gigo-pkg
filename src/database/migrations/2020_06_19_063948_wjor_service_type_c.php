<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WjorServiceTypeC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('wjor_service_type')) {
			Schema::create('wjor_service_type', function (Blueprint $table) {
				$table->unsignedInteger('wjor_id');
				$table->unsignedInteger('service_type_id');

				$table->foreign('wjor_id')->references('id')->on('warranty_job_order_requests')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('service_type_id')->references('id')->on('service_types')->onDelete('CASCADE')->onUpdate('cascade');

				$table->unique(["wjor_id", "service_type_id"]);
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('wjor_service_type');
	}
}
