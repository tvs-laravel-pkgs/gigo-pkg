<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RepairOrderMechanicsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		if (!Schema::hasTable('repair_order_mechanics')) {
			Schema::create('repair_order_mechanics', function (Blueprint $table) {

				$table->increments('id');
				$table->unsignedInteger('job_order_repair_order_id');
				$table->unsignedInteger('mechanic_id');
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("job_order_repair_order_id", 'rom_fk1')->references("id")->on("job_order_repair_orders")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("mechanic_id", 'rom_fk2')->references("id")->on("users")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("created_by_id", 'rom_fk3')->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id", 'rom_fk4')->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id", 'rom_fk5')->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->unique(["job_order_repair_order_id", "mechanic_id"], 'rom_uk1');

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('repair_order_mechanics');
	}
}
