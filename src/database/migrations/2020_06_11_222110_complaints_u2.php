<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ComplaintsU2 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('complaints', function (Blueprint $table) {
			$table->dropUnique("complaints_company_id_group_id_name_unique");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('complaints', function (Blueprint $table) {
			$table->unique(["company_id", "group_id", "name"]);
		});
	}
}
