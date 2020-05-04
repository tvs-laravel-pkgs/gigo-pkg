<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VehiclesC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::dropIfExists('gate_logs');
		Schema::dropIfExists('vehicles');

		if (!Schema::hasTable('vehicles')) {
			Schema::create('vehicles', function (Blueprint $table) {

				$table->increments('id');
				$table->unsignedInteger('company_id');
				$table->string('engine_number', 64);
				$table->string('chassis_number', 64);
				$table->unsignedInteger('model_id')->nullable();
				$table->boolean('is_registered');
				$table->string('registration_number', 10)->nullable();
				$table->string('vin_number', 32)->nullable();
				$table->date('sold_date')->nullable();
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("model_id")->references("id")->on("models")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("company_id")->references("id")->on("companies")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->unique(["company_id", "engine_number"]);
				$table->unique(["company_id", "chassis_number"]);
				$table->unique(["company_id", "registration_number"]);
				$table->unique(["company_id", "vin_number"]);

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('vehicles');
	}
}
