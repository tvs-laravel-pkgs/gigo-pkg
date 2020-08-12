<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubAggregatesColComplaintsTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('complaints', function (Blueprint $table) {
			DB::statement('SET FOREIGN_KEY_CHECKS=0;');

			// $table->dropForeign('complaints_company_id_foreign');
			$table->dropForeign('complaints_group_id_foreign');

			$table->dropUnique('complaints_company_id_group_id_code_unique');
			$table->dropColumn('group_id');

			$table->unsignedInteger('sub_aggregate_id')->after('name')->nullable();
			$table->unique(['company_id', 'sub_aggregate_id', 'code']);
			$table->foreign('sub_aggregate_id')->references('id')->on('sub_aggregates')->onDelete('CASCADE')->onUpdate('CASCADE');
			DB::statement('SET FOREIGN_KEY_CHECKS=1;');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('complaints', function (Blueprint $table) {
			DB::statement('SET FOREIGN_KEY_CHECKS=0;');

			$table->dropUnique('complaints_company_id_sub_aggregate_id_code_unique');
			$table->dropForeign('complaints_sub_aggregate_id_foreign');
			$table->dropColumn('sub_aggregate_id');

			$table->unsignedInteger('group_id')->after('name')->nullable();
			$table->foreign("group_id")->references("id")->on("complaint_groups")->onDelete("CASCADE")->onUpdate("CASCADE");

			$table->unique(['company_id', 'group_id', 'code']);

			DB::statement('SET FOREIGN_KEY_CHECKS=1;');

		});
	}
}
