<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableServiceTypeAddCols extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('part_service_type', function (Blueprint $table) {
			$table->boolean('is_free_service')->nullable()->after('amount');
		});

		Schema::table('repair_order_service_type', function (Blueprint $table) {
			$table->boolean('is_free_service')->nullable()->after('service_type_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('part_service_type', function (Blueprint $table) {
			$table->dropColumn('is_free_service');
		});
		Schema::table('repair_order_service_type', function (Blueprint $table) {
			$table->dropColumn('is_free_service');
		});
	}
}
