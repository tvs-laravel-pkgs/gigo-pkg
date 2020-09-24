<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableJobOrderPartsAddVoc extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_order_parts', function (Blueprint $table) {
			$table->unsignedInteger('customer_voice_id')->nullable()->after('job_order_id');

			$table->foreign('customer_voice_id')->references('id')->on('customer_voices')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_order_parts', function (Blueprint $table) {
			$table->dropForeign('job_order_parts_customer_voice_id_foreign');

			$table->dropColumn('customer_voice_id');
		});
	}
}
