<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTableSiteOrdrReturnedParts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
		Schema::create('on_site_order_returned_parts', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('on_site_order_part_id');
			$table->unsignedDecimal('returned_qty', 16,2);
			$table->unsignedInteger('returned_to_id');
			$table->text('remarks');
			$table->unsignedInteger('created_by_id');
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->timestamps();
			$table->softDeletes();
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('returned_to_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('on_site_order_part_id')->references('id')->on('on_site_order_parts')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('on_site_order_returned_parts');
	}
}
