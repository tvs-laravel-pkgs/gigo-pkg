<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PartsUpdate138 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('parts', function (Blueprint $table) {
			$table->dropColumn('rate');
			$table->dropColumn('mrp');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('parts', function (Blueprint $table) {
			$table->unsignedDecimal('rate')->after('uom_id');
			$table->unsignedDecimal('mrp')->after('rate');
		});
	}
}
