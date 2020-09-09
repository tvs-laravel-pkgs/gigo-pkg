<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableSurveyTypesAddUnique extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('survey_types', function (Blueprint $table) {
			$table->unique(['company_id', 'attendee_type_id', 'survey_trigger_event_id'], 'survey_type_company_attendee_event_id_unique');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('survey_types', function (Blueprint $table) {
			$table->dropForeign('survey_types_attendee_type_id_foreign');
			$table->dropForeign('survey_types_survey_trigger_event_id_foreign');
			$table->dropForeign('survey_types_company_id_foreign');

			$table->dropUnique('survey_type_company_attendee_event_id_unique');

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('attendee_type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
			$table->foreign('survey_trigger_event_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
		});
	}
}
