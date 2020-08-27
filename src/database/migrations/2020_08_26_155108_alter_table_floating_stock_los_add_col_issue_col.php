<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableFloatingStockLosAddColIssueCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('floating_stock_logs', function (Blueprint $table) {
			$table->unsignedInteger('issued_to_id')->nullable()->after('inward_remarks');
			$table->unsignedInteger('returned_to_id')->nullable()->after('issued_to_id');
			$table->foreign('issued_to_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('returned_to_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('floating_stock_logs', function (Blueprint $table) {
			$table->dropForeign('floating_stock_logs_issued_to_id_foreign');
			$table->dropForeign('floating_stock_logs_returned_to_id_foreign');
			$table->dropColumn('issued_to_id');
			$table->dropColumn('returned_to_id');
		});
	}
}
