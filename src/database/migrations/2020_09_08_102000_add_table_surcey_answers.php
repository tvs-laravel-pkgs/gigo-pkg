<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableSurceyAnswers extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('survey_answers', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('survey_id');
			$table->unsignedInteger('survey_type_field_id');
			$table->string('answer')->nullable();

			$table->unique(['survey_id', 'survey_type_field_id']);
			$table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('survey_type_field_id')->references('id')->on('fields')->onDelete('cascade')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('survey_answers');
	}
}
