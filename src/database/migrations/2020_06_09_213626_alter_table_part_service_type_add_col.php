<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTablePartServiceTypeAddCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('part_service_type', function (Blueprint $table) {
			$table->unsignedDecimal('quantity', 16, 2)->nullable()->after('service_type_id');
			$table->unsignedDecimal('amount', 16, 2)->nullable()->after('quantity');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('part_service_type', function (Blueprint $table) {
			$table->dropColumn('quantity');
			$table->dropColumn('amount');
		});
	}
}
