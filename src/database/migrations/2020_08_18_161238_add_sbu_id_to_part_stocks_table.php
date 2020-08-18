<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSbuIdToPartStocksTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('part_stocks', function (Blueprint $table) {
			$table->Integer('sbu_id')->nullable()->after('mrp')->comment('used in Vims Parts request');
			$table->foreign('sbu_id')->references('id')->on('sbus')->onDelete('CASCADE')->onUpdate('cascade');
			//drop old unique
			$table->dropForeign('part_stocks_company_id_foreign');
			$table->dropForeign('part_stocks_outlet_id_foreign');
			$table->dropForeign('part_stocks_part_id_foreign');
			$table->dropUnique('unique_company_outlet_part_id');

			//add new unique
			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('part_id')->references('id')->on('parts')->onDelete('cascade')->onUpdate('cascade');
			$table->unique(['company_id', 'outlet_id', 'part_id', 'sbu_id'], 'unique_company_outlet_part_id_sbu_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('part_stocks', function (Blueprint $table) {
			//drop old unique
			$table->dropForeign('part_stocks_company_id_foreign');
			$table->dropForeign('part_stocks_outlet_id_foreign');
			$table->dropForeign('part_stocks_part_id_foreign');
			$table->dropForeign('part_stocks_sbu_id_foreign');
			$table->dropUnique('unique_company_outlet_part_id_sbu_id');
			$table->dropColumn('sbu_id');

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('part_id')->references('id')->on('parts')->onDelete('cascade')->onUpdate('cascade');
			$table->unique(['company_id', 'outlet_id', 'part_id'], 'unique_company_outlet_part_id');

		});
	}
}
