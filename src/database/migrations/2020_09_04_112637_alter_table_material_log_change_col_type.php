<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableMaterialLogChangeColType extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('material_inward_logs', function (Blueprint $table) {
			$table->dateTime('created_at')->change();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('material_inward_logs', function (Blueprint $table) {
			$table->date('created_at')->change();
		});

	}
}
