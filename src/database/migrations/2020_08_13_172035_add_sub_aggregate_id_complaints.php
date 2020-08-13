<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubAggregateIdComplaints extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		// dd("1");
		Schema::table('complaints', function (Blueprint $table) {
			$table->unsignedInteger('group_id')->nullable()->change();
			$table->unsignedInteger('sub_aggregate_id')->after('group_id')->nullable();
			$table->foreign('sub_aggregate_id')->references('id')->on('sub_aggregates')->onDelete('CASCADE')->onUpdate('CASCADE');
			$table->unique(['company_id', 'sub_aggregate_id', 'code']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('complaints', function (Blueprint $table) {
			$table->dropUnique('complaints_company_id_sub_aggregate_id_code_unique');
			$table->dropForeign('complaints_sub_aggregate_id_foreign');
			$table->dropColumn('sub_aggregate_id');
		});
	}
}
