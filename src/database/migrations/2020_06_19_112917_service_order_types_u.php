<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ServiceOrderTypesU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('service_order_types', function (Blueprint $table) {
			$table->boolean('is_expert_diagnosis_required')->default(0)->after('code');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('service_order_types', function (Blueprint $table) {
			$table->dropColumn("is_expert_diagnosis_required");
		});
	}
}
