<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableSurveyTypeFields extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('survey_type_fields', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('survey_type_id');
			$table->unsignedInteger('field_id');
			$table->foreign('survey_type_id')->references('id')->on('survey_types')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('field_id')->references('id')->on('fields')->onDelete('cascade')->onUpdate('cascade');

			$table->unique(['survey_type_id', 'field_id']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('survey_type_fields');
	}
}
