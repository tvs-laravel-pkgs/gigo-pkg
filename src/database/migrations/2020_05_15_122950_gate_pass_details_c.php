<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GatePassDetailsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('gate_pass_details')) {
			Schema::create('gate_pass_details', function (Blueprint $table) {

				$table->increments('id');
				$table->unsignedInteger('gate_pass_id');
				$table->unsignedInteger('vendor_type_id');
				$table->unsignedInteger('vendor_id');
				$table->string('work_order_no', 64)->nullable();
				$table->string('work_order_description', 191)->nullable();
				$table->string('vendor_contact_no', 64)->nullable();
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("gate_pass_id")->references("id")->on("gate_passes")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("vendor_type_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("vendor_id")->references("id")->on("vendors")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->unique(["gate_pass_id"]);
				$table->unique(["work_order_no"]);

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('gate_pass_details');
	}
}
