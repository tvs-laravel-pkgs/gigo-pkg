<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Abs\StatusPkg\Status;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Survey extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'surveys';
	public $timestamps = true;
	protected $fillable =
		["id", "company_id", "number", "survey_of_id", "survey_for_id", "survey_type_id", "status_id"]
	;

	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}

	public function surveyOf() {
		return $this->belongsTo('App\Config', 'survey_of_id');
	}

	public function surveyType() {
		return $this->belongsTo('App\SurveyType', 'survey_type_id');
	}

	public function surveyAnswer() {
		return $this->belongsToMany('Abs\AttributePkg\Field', 'survey_answers', 'survey_id', 'survey_type_field_id')->withPivot(['answer']);
	}

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
	}

}
