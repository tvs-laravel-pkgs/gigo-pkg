<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterOwnerTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::dropIfExists('vehicle_owners');

		Schema::create('vehicle_owners', function (Blueprint $table) {

			$table->increments('id');
			$table->unsignedInteger('vehicle_id');
			$table->unsignedInteger('customer_id');
			$table->date('from_date');
			$table->unsignedInteger('ownership_id')->nullable();
			$table->unsignedInteger("created_by_id")->nullable();
			$table->unsignedInteger("updated_by_id")->nullable();
			$table->unsignedInteger("deleted_by_id")->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->foreign("vehicle_id")->references("id")->on("vehicles")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->foreign("customer_id")->references("id")->on("customers")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->foreign("ownership_id")->references("id")->on("configs")->onDelete("SET NULL")->onUpdate("CASCADE");
			$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
			$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
			$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

			$table->unique(["vehicle_id", "customer_id"]);
			$table->unique(["vehicle_id", "ownership_id"]);

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('vehicle_owners');
	}

}
