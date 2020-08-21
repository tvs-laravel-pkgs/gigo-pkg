<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTblFloatingStock extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('floating_stocks', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id');
			$table->unsignedInteger('outlet_id');
			$table->unsignedInteger('part_id');
			$table->unsignedDecimal('qty', 8, 2);
			$table->unsignedDecimal('issued_qty', 8, 2);
			$table->unsignedDecimal('available_qty', 8, 2);
			$table->unsignedInteger('created_by_id');
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->timestamps();
			$table->softDeletes();
			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('part_id')->references('id')->on('parts')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('floating_stocks');
	}
}
