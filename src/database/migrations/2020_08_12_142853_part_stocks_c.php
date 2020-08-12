<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PartStocksC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('part_stocks')) {
			Schema::create('part_stocks', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('company_id');
				$table->unsignedInteger('outlet_id');
				$table->unsignedInteger('part_id');
				$table->unsignedDecimal('stock', 12, 2);
				$table->unsignedDecimal('cost_price', 12, 2);
				$table->unsignedDecimal('mrp', 12, 2);
				$table->unsignedInteger('created_by_id')->nullable();
				$table->unsignedInteger('updated_by_id')->nullable();
				$table->unsignedInteger('deleted_by_id')->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign("outlet_id")->references("id")->on("outlets")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("part_id")->references("id")->on("parts")->onDelete("CASCADE")->onUpdate("CASCADE");

				$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('part_stocks');
	}
}
