<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WjorPartsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('wjor_parts')) {
			Schema::create('wjor_parts', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('wjor_id');
				$table->unsignedInteger('part_id');

				$table->foreign('wjor_id')->references('id')->on('warranty_job_order_requests')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('part_id')->references('id')->on('parts')->onDelete('CASCADE')->onUpdate('cascade');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('wjor_parts');
	}
}
