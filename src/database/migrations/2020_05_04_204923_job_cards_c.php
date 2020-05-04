<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JobCardsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		if (!Schema::hasTable('job_cards')) {
			Schema::create('job_cards', function (Blueprint $table) {

				$table->increments('id');
				$table->unsignedInteger('job_order_id');
				$table->string('number', 64);
				$table->string('order_number', 64)->nullable();
				$table->unsignedInteger('floor_supervisor_id')->nullable();
				$table->unsignedInteger('status_id');
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("job_order_id")->references("id")->on("job_orders")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("floor_supervisor_id")->references("id")->on("users")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("status_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->unique(["job_order_id"]);
				$table->unique(["number"]);
				$table->unique(["order_number"]);

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('job_cards');
	}
}
