<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableJobOrderPartsAddFixedCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_order_parts', function (Blueprint $table) {
			$table->tinyInteger('is_fixed_schedule')->default(0)->after('is_oem_recommended')->comment('1-Fixed,0-Editable');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_order_parts', function (Blueprint $table) {
			$table->dropColumn('is_fixed_schedule');
		});
	}
}
