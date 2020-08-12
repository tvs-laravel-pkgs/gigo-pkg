<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClaimCategoryColSplitOrderType extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('split_order_types', function (Blueprint $table) {
			$table->unsignedInteger('claim_category_id')->after('name')->nullable();
			$table->foreign("claim_category_id")->references("id")->on("configs")->onDelete("SET NULL")->onUpdate("CASCADE");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('split_order_types', function (Blueprint $table) {
			$table->dropForeign('split_order_types_claim_category_id_foreign');
			$table->dropColumn("claim_category_id");
		});
	}
}
