<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableVoc extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('customer_voices', function (Blueprint $table) {

			$table->dropForeign('customer_voices_company_id_foreign');
			$table->dropForeign('customer_voices_lv_main_type_id_foreign');

			$table->dropUnique('customer_voices_company_id_code_unique');
			$table->dropUnique('customer_voices_company_id_name_unique');

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('lv_main_type_id')->references('id')->on('lv_main_types')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(['company_id', 'code', 'name', 'lv_main_type_id'], 'company_code_name_lv_main_type_unique');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('customer_voices', function (Blueprint $table) {

			$table->dropForeign('customer_voices_company_id_foreign');
			$table->dropForeign('customer_voices_lv_main_type_id_foreign');

			$table->dropUnique('company_code_name_lv_main_type_unique');

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('lv_main_type_id')->references('id')->on('lv_main_types')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(['company_id', 'name']);
			$table->unique(['company_id', 'code']);

		});
	}
}
