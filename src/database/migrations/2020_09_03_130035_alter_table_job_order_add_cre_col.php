<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableJobOrderAddCreCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropForeign('job_orders_cre_user_id_foreign');
			$table->dropColumn('cre_user_id');

			$table->string('cre_name', 100)->nullable()->after('is_appointment');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->unsignedInteger('cre_user_id')->nullable()->after('is_appointment');
			$table->foreign('cre_user_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');

			$table->dropColumn('cre_name');
		});
	}
}
