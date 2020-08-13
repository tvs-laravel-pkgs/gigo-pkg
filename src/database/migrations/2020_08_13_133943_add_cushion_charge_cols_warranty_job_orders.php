<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCushionChargeColsWarrantyJobOrders extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('warranty_job_order_requests', function (Blueprint $table) {

			$table->decimal('total_labour_amount', 12, 2)->after('cause_of_failure')->default(0);
			$table->unsignedInteger('total_part_cushioning_percentage')->after('total_labour_amount')->default(0);
			$table->decimal('total_part_cushioning_charge', 12, 2)->after('total_part_cushioning_percentage')->default(0);
			$table->decimal('total_part_amount', 12, 2)->after('total_part_cushioning_charge')->default(0);

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('warranty_job_order_requests', function (Blueprint $table) {

			$table->dropColumn([
				'total_labour_amount',
				'total_part_cushioning_percentage',
				'total_part_cushioning_charge',
				'total_part_amount',
			]);

		});
	}
}
