<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CompaignRepairOrderC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('compaign_repair_order')) {
			Schema::create('compaign_repair_order', function (Blueprint $table) {

				$table->unsignedInteger('compaign_id');
				$table->unsignedInteger('repair_order_id');
				$table->unsignedDecimal('amount', 12, 2);

				$table->foreign("compaign_id")->references("id")->on("compaigns")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("repair_order_id")->references("id")->on("repair_orders")->onDelete("CASCADE")->onUpdate("CASCADE");

				$table->unique(['compaign_id', 'repair_order_id']);
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('compaign_repair_order');
	}
}
