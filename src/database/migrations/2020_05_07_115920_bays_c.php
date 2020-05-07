<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BaysC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('bays')) {
			Schema::create('bays', function (Blueprint $table) {

				$table->increments('id');
				$table->unsignedInteger('outlet_id');
				$table->string('short_name', 32);
				$table->string('name', 128)->nullable();
				$table->unsignedInteger('status_id');
				$table->unsignedInteger('job_order_id')->nullable();
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("outlet_id")->references("id")->on("outlets")->onDelete("SET NULL")->onUpdate("CASCADE");
				$table->foreign("job_order_id")->references("id")->on("job_orders")->onDelete("SET NULL")->onUpdate("CASCADE");
				$table->foreign("status_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->unique(["outlet_id", "short_name"]);
				$table->unique(["outlet_id", "name"]);
				$table->unique(["job_order_id"]);

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('bays');
	}
}
