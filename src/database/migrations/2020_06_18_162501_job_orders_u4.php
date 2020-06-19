<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobOrdersU4 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->unsignedInteger('customer_id')->nullable()->after("vehicle_id");
			$table->foreign("customer_id")->references("id")->on("customers")->onDelete("CASCADE")->onUpdate("CASCADE");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropForeign("job_orders_customer_id_foreign");
			$table->dropColumn("customer_id");
		});
	}
}
