<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class SurveyAnswer extends BaseModel {
	use SeederTrait;
	protected $table = 'survey_answers';
	public $timestamps = false;
	protected $fillable =
		["id", "survey_id", "survey_type_field_id"]
	;

	// public function attendeeType() {
	// 	return $this->belongsTo('App\Config', 'attendee_type_id');
	// }

	// public function surveyField() {
	// 	return $this->belongsToMany('Abs\AttributePkg\Field', 'survey_type_fields', 'survey_type_id', 'field_id');
	// }

}
