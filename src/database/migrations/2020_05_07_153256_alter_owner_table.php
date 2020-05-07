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
			$table->unsignedInteger('ownership_id');

			$table->foreign("vehicle_id")->references("id")->on("vehicles")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->foreign("customer_id")->references("id")->on("customers")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->foreign("ownership_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");

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
		Schema::create('vehicle_owners', function (Blueprint $table) {

			$table->unsignedInteger('vehicle_id');
			$table->unsignedInteger('customer_id');
			$table->date('from_date');
			$table->unsignedTinyInteger('ownership_number');
			$table->increments('id');

			$table->foreign("vehicle_id")->references("id")->on("vehicles")->onDelete("CASCADE")->onUpdate("CASCADE");
			$table->foreign("customer_id")->references("id")->on("customers")->onDelete("CASCADE")->onUpdate("CASCADE");

			$table->unique(["vehicle_id", "customer_id"]);
			$table->unique(["vehicle_id", "ownership_number"]);

		});
	}

}
