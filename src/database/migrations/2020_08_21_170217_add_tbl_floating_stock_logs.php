<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTblFloatingStockLogs extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('floating_stock_logs', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id');
			$table->unsignedInteger('outlet_id');
			$table->string('number', 191);
			$table->unsignedInteger('job_card_id');
			$table->unsignedInteger('floating_stock_id');
			$table->unsignedDecimal('qty', 8, 2);
			$table->dateTime('outward_date')->nullable();
			$table->dateTime('inward_date')->nullable();
			$table->unsignedInteger('status_id');
			$table->unsignedInteger('created_by_id');
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->timestamps();
			$table->softDeletes();
			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('job_card_id')->references('id')->on('job_cards')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('floating_stock_id')->references('id')->on('floating_stocks')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
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
		Schema::dropIfExists('floating_stock_logs');
	}
}
