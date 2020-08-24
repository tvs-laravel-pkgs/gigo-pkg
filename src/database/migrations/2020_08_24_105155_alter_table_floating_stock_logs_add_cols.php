<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableFloatingStockLogsAddCols extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('floating_stock_logs', function (Blueprint $table) {
			$table->string('outward_remarks')->nullable()->after('outward_date');
			$table->string('inward_remarks')->nullable()->after('inward_date');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('floating_stock_logs', function (Blueprint $table) {
			$table->dropColumn('outward_remarks');
			$table->dropColumn('inward_remarks');
		});
	}
}
