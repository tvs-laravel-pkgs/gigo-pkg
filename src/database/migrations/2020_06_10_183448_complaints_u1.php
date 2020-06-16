<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ComplaintsU1 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('complaints', function (Blueprint $table) {
			$table->foreign("group_id")->references("id")->on("complaint_groups")->onDelete("CASCADE")->onUpdate("CASCADE");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('complaints', function (Blueprint $table) {
			$table->dropForeign('complaints_group_id_foreign');
		});
	}
}
