<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableOtps extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('otps', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('entity_type_id');
			$table->unsignedInteger('entity_id');
			$table->string('otp_no', 25);
			$table->unsignedInteger('created_by_id');
			$table->dateTime('created_at');
			$table->dateTime('expired_at');
			$table->foreign('entity_type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('otps');
	}
}
