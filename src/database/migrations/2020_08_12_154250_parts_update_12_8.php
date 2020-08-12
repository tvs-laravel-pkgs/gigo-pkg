<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PartsUpdate128 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('parts', function (Blueprint $table) {

			$table->dropForeign('parts_company_id_foreign');

			$table->dropUnique('parts_company_id_code_unique');
			$table->dropUnique('parts_company_id_name_unique');

			$table->unsignedInteger('part_type_id')->nullable()->after('name');

			$table->foreign("part_type_id")->references("id")->on("part_types")->onDelete("SET NULL")->onUpdate("CASCADE");

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(["company_id", "code", "name"]);

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('parts', function (Blueprint $table) {
			$table->dropForeign('parts_part_type_id_foreign');
			$table->dropForeign('parts_company_id_foreign');

			$table->dropUnique('parts_company_id_code_name_unique');

			$table->dropColumn('part_type_id');

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(["company_id", "code"]);
			$table->unique(["company_id", "name"]);
		});
	}
}
