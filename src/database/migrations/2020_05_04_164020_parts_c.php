<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PartsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		if (!Schema::hasTable('parts')) {
			Schema::create('parts', function (Blueprint $table) {

				$table->increments('id');
				$table->unsignedInteger('company_id');
				$table->string('code', 32);
				$table->string('name', 191)->nullable();
				$table->unsignedInteger('uom_id')->nullable();
				$table->unsignedDecimal('rate', 12, 2);
				$table->unsignedInteger('tax_code_id')->nullable();
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("company_id")->references("id")->on("companies")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("uom_id")->references("id")->on("uoms")->onDelete("SET NULL")->onUpdate("SET NULL");
				$table->foreign("tax_code_id")->references("id")->on("tax_codes")->onDelete("SET NULL")->onUpdate("SET NULL");
				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->unique(["company_id", "code"]);
				$table->unique(["company_id", "name"]);

			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('parts');
	}
}
