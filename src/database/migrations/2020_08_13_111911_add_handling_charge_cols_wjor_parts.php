<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHandlingChargeColsWjorParts extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('wjor_parts', function (Blueprint $table) {

			$table->unsignedInteger('handling_charge_percentage')->after('net_amount')->default(0);
			$table->decimal('handling_charge')->after('handling_charge_percentage')->default(0);

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('wjor_parts', function (Blueprint $table) {

			$table->dropColumn('handling_charge_percentage');
			$table->dropColumn('handling_charge');

		});
	}
}
