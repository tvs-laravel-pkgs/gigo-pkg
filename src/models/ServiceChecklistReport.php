<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceChecklistReport extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'service_checklist_reports';
	public $timestamps = true;

	public static $AUTO_GENERATE_CODE = true;

	protected $fillable = [
		"company_id",
		"job_card_id",
		"number",
		"date",
		"inspected_by_id",
	];

	protected $dates = [
		'date',
		'created_at',
		'updated_at',
		'deleted_at',
	];

	protected $casts = [
	];

	// Getter & Setters --------------------------------------------------------------

	// Relations --------------------------------------------------------------

	public function jobCard() {
		return $this->belongsTo('App\JobCard');
	}

	public function inspectedBy() {
		return $this->belongsTo('App\Employee', 'inspected_by_id');
	}

	public function serviceCheckLists() {
		return $this->belongsToMany('App\ServiceCheckList', 'service_checklist_report_details', 'report_id', 'service_check_list_id')->withPivot([
			'action_and_observation_taken',
		]);
	}

	// Static Operations --------------------------------------------------------------

	public static function validateFormInput($data, $user) {
		$error_messages = [
			'code.required' => 'Code is Required',
			'code.unique' => 'Code already taken',
			'code.min' => 'Code should have minimum 3 Charachers',
			'code.max' => 'Code should have maximum 32 Charachers',
			'name.required' => 'Name is Required',
			'name.unique' => 'Name already taken',
			'name.min' => 'Name should have minimum 3 Charachers',
			'name.max' => 'Name should have maximum 191 Charachers',
		];
		$validator = Validator::make($data, [
			'code' => [
				'required:true',
				'min:3',
				'max:32',
			],
			'name' => [
				'required:true',
				'min:3',
				'max:191',
			],
		], $error_messages);
		if ($validator->fails()) {
			return [
				'success' => false,
				'errors' => $validator->errors()->all(),
			];
		}
		return [
			'success' => true,
			'errors' => [],
		];
	}

}
