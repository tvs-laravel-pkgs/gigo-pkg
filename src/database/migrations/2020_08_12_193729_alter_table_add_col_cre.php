<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableAddColCre extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->tinyInteger('is_appointment')->nullable()->after('insurance_expiry_date');
			$table->unsignedInteger('cre_user_id')->nullable()->after('is_appointment');
			$table->date('call_date')->nullable()->after('cre_user_id');
			$table->foreign('cre_user_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropForeign('job_orders_cre_user_id_foreign');
			$table->dropColumn('is_appointment');
			$table->dropColumn('cre_user_id');
			$table->dropColumn('call_date');
		});
	}
}
