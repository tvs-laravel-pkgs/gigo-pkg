<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UServiceTypesAddDisplayOrderColumn extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('service_types', function (Blueprint $table) {
			$table->unsignedInteger('display_order')->default(99)->after('name');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('service_types', function (Blueprint $table) {
			$table->dropColumn('display_order');
		});
	}
}
