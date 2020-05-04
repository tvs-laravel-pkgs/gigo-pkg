<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsuranceMembersC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		if (!Schema::hasTable('insurance_members')) {
			Schema::create('insurance_members', function (Blueprint $table) {

				$table->increments('id');
				$table->unsignedInteger('company_id');
				$table->unsignedInteger('vehicle_id');
				$table->unsignedInteger('policy_id');
				$table->string('number', 64)->nullable();
				$table->date('expiry_date');
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("vehicle_id")->references("id")->on("vehicles")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("policy_id")->references("id")->on("insurance_policies")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("company_id")->references("id")->on("companies")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->unique(["company_id", "policy_id", "number"]);

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('insurance_members');
	}
}
