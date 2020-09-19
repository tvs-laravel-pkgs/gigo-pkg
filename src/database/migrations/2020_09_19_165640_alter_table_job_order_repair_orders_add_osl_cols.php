<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableJobOrderRepairOrdersAddOslCols extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_order_repair_orders', function (Blueprint $table) {
			$table->tinyInteger('is_work_order')->default(0)->after('split_order_type_id')->comment('0 : No, 1 : Yes');
			$table->unsignedInteger('osl_work_order_id')->nullable()->after('is_work_order');

			$table->foreign('osl_work_order_id')->references('id')->on('osl_work_orders')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_order_repair_orders', function (Blueprint $table) {
			$table->dropForeign('job_order_repair_orders_osl_work_order_id_foreign');

			$table->dropColumn('osl_work_order_id');
			$table->dropColumn('is_work_order');
		});
	}
}
