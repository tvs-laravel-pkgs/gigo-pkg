<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTablePprRejectReasons extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('ppr_reject_reasons', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id');
			$table->string('name', 200);
			$table->unsignedinteger('created_by_id')->nullable();
			$table->unsignedinteger('updated_by_id')->nullable();
			$table->unsignedinteger('deleted_by_id')->nullable();
			$table->timestamp('created_at')->useCurrent();
			$table->timestamp('updated_at')->nullable();
			$table->timestamp('deleted_at')->nullable();
			$table->foreign('created_by_id')->references('id')->on('users');
			$table->foreign('updated_by_id')->references('id')->on('users');
			$table->foreign('deleted_by_id')->references('id')->on('users');
			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('ppr_reject_reasons');
	}
}
