<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableSiteVisitParts extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('on_site_order_parts')) {
			Schema::create('on_site_order_parts', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('on_site_order_id');
				$table->unsignedInteger('part_id');
				$table->tinyInteger('is_customer_approved')->nullable()->comment('1 -> Yes, 0 -> No');
				$table->unsignedInteger('split_order_type_id');
				$table->unsignedInteger('qty');
				$table->unsignedDecimal('rate',16,2);
				$table->unsignedDecimal('amount',16,2);
				$table->unsignedInteger('removal_reason_id')->nullable();
				$table->string('removal_reason', 191)->nullable();
				$table->unsignedInteger('status_id');
				$table->unsignedInteger('created_by_id')->nullable();
				$table->unsignedInteger('updated_by_id')->nullable();
				$table->unsignedInteger('deleted_by_id')->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign('on_site_order_id')->references('id')->on('on_site_orders')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('part_id')->references('id')->on('parts')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('split_order_type_id')->references('id')->on('split_order_types')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign("removal_reason_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign('status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');

				$table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('on_site_order_parts');
	}
}
