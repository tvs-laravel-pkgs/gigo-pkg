<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableOtpAddOutletId extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('otps', function (Blueprint $table) {
			$table->unsignedInteger('outlet_id')->nullable()->after('id');

			$table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('otps', function (Blueprint $table) {
			$table->dropForeign('otps_outlet_id_foreign');

			$table->dropColumn('outlet_id');
		});
	}
}
