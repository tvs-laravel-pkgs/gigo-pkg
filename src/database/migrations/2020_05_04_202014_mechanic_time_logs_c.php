<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MechanicTimeLogsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		if (!Schema::hasTable('mechanic_time_logs')) {
			Schema::create('mechanic_time_logs', function (Blueprint $table) {

				$table->increments('id');
				$table->unsignedInteger('repair_order_mechanic_id');
				$table->datetime('start_date_time');
				$table->datetime('end_date_time')->nullable();
				$table->unsignedInteger('status_id');
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("repair_order_mechanic_id")->references("id")->on("repair_order_mechanics")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("status_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('mechanic_time_logs');
	}
}
