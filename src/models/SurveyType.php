<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyType extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'survey_types';
	public $timestamps = true;
	protected $fillable =
		["id", "company_id", "number", "name", "attendee_type_id", "purpose", "survey_trigger_event_id"]
	;

	public function attendeeType() {
		return $this->belongsTo('App\Config', 'attendee_type_id');
	}

	public function surveyField() {
		return $this->belongsToMany('Abs\AttributePkg\Models\Field', 'survey_type_fields', 'survey_type_id', 'field_id');
	}
}
