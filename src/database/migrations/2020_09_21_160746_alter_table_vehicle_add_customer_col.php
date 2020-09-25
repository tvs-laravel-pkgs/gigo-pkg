<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableVehicleAddCustomerCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('vehicles', function (Blueprint $table) {
			$table->unsignedInteger('customer_id')->nullable()->after('registration_number');

			$table->foreign('customer_id')->references('id')->on('customers')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('vehicles', function (Blueprint $table) {
			$table->dropForeign('vehicles_customer_id_foreign');

			$table->dropColumn('customer_id');
		});
	}
}
