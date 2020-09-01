<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFailureTypesTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('failure_types', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id');
			$table->string('name');

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
			$table->unique(['company_id', 'name']);

			$table->unsignedinteger('created_by');
			$table->unsignedinteger('updated_by')->nullable();
			$table->unsignedinteger('deleted_by')->nullable();
			$table->timestamps();

			$table->foreign('created_by')->references('id')->on('users')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('updated_by')->references('id')->on('users')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('deleted_by')->references('id')->on('users')->onDelete('CASCADE')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('failure_types');
	}
}
