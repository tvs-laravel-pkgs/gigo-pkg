<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableBaysAddColDisplayOrder extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('bays', function (Blueprint $table) {
			$table->unsignedInteger('display_order')->nullable()->after('name');
			$table->unique(['outlet_id', 'display_order']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('bays', function (Blueprint $table) {
			$table->dropForeign('bays_outlet_id_foreign');
			$table->dropUnique('bays_outlet_id_display_order_unique');
			$table->dropColumn('display_order');

			$table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
		});
	}
}
