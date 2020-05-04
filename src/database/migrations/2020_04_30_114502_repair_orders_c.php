<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RepairOrdersC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('repair_orders')) {
			Schema::create('repair_orders', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('company_id');
				$table->unsignedInteger('type_id');
				$table->string('code', 36);
				$table->string('alt_code', 36)->nullable();
				$table->string('name', 128);
				$table->unsignedInteger('uom_id')->nullable();
				$table->unsignedInteger('skill_level_id')->nullable();
				$table->unsignedMediumInteger('hours');
				$table->unsignedDecimal('amount', 12, 2);
				$table->unsignedInteger('tax_code_id')->nullable();
				$table->unsignedInteger('created_by_id')->nullable();
				$table->unsignedInteger('updated_by_id')->nullable();
				$table->unsignedInteger('deleted_by_id')->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign('company_id')->references('id')->on('companies')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('uom_id')->references('id')->on('uoms')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('type_id')->references('id')->on('repair_order_types')->onDelete('CASCADE')->onUpdate('cascade');

				$table->foreign('skill_level_id')->references('id')->on('skill_levels')->onDelete('SET NULL')->onUpdate('cascade');

				$table->foreign('tax_code_id')->references('id')->on('tax_codes')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');

				$table->unique(["company_id", "type_id", "code"]);
				$table->unique(["company_id", "type_id", "alt_code"]);
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('repair_orders');
	}
}
