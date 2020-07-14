<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EmployeeUpdate extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('employees', function (Blueprint $table) {
			$table->unsignedInteger('shift_id')->nullable()->after('is_mechanic');
			$table->foreign("shift_id")->references("id")->on("shifts")->onDelete("SET NULL")->onUpdate("CASCADE");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('employees', function (Blueprint $table) {
			$table->dropForeign("employees_shift_id_foreign");
			$table->dropColumn('shift_id');
		});
	}
}
