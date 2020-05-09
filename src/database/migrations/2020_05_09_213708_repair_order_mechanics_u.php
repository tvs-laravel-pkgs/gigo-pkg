<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RepairOrderMechanicsU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('repair_order_mechanics', function (Blueprint $table) {
			$table->unsignedInteger('status_id')->nullable()->after('mechanic_id');
			$table->foreign("status_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('repair_order_mechanics', function (Blueprint $table) {
			$table->dropForeign("repair_order_mechanics_status_id_foreign");
			$table->dropColumn('status_id');
		});
	}
}
