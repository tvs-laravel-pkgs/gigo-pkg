<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTableSiteOrdrIssuedParts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
		Schema::create('on_site_order_issued_parts', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('on_site_order_part_id');
			$table->unsignedDecimal('issued_qty', 16,2);
			$table->unsignedInteger('issued_to_id');
			$table->unsignedInteger('issued_mode_id');
			$table->unsignedInteger('created_by_id');
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->timestamps();
			$table->softDeletes();
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('issued_mode_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('issued_to_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('on_site_order_part_id')->references('id')->on('on_site_order_parts')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('on_site_order_issued_parts');
	}
}
