<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterVehicleTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('vehicles', function (Blueprint $table) {
			$table->string('engine_number', 64)->nullable()->change();
			$table->string('chassis_number', 64)->nullable()->change();

			$table->unsignedInteger('status_id')->nullable()->after('sold_date');

			$table->foreign('status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');

		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('vehicles', function (Blueprint $table) {
			$table->dropForeign('vehicles_status_id_foreign');

			$table->dropColumn('status_id');
			$table->string('engine_number', 64)->change();
			$table->string('chassis_number', 64)->change();
		});
	}
}
