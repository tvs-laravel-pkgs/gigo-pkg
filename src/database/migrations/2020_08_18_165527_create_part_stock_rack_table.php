<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartStockRackTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('part_stock_rack', function (Blueprint $table) {
			$table->unsignedInteger('part_stock_id');
			$table->unsignedInteger('rack_id');
			$table->Integer('quantity');
			$table->foreign('part_stock_id')->references('id')->on('part_stocks')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('rack_id')->references('id')->on('racks')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('part_stock_rack');
	}
}
