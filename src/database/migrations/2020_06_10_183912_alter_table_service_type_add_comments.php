<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableServiceTypeAddComments extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('part_service_type', function (Blueprint $table) {
			$table->boolean('is_free_service')->nullable()->change()->comment('1 => Yes, 0 => No');
		});

		Schema::table('repair_order_service_type', function (Blueprint $table) {
			$table->boolean('is_free_service')->nullable()->change()->comment('1 => Yes, 0 => No');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('part_service_type', function (Blueprint $table) {
			$table->boolean('is_free_service')->nullable()->change();
		});

		Schema::table('repair_order_service_type', function (Blueprint $table) {
			$table->boolean('is_free_service')->nullable()->change();
		});
	}
}
