<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceChecklist extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'service_checklists';
	public $timestamps = true;

	public static $AUTO_GENERATE_CODE = false;

	protected $fillable = [
		"company_id",
		"segment_id",
		"component_group_id",
		"maintenence_activity",
		"display_order",
	];

	protected $dates = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	protected $casts = [
	];

	// Getter & Setters --------------------------------------------------------------

	// Relations --------------------------------------------------------------

	public function segment() {
		return $this->belongsTo('App\VehicleSegment', 'segment_id');
	}

	public function componentGroup() {
		return $this->belongsTo('App\VehicleComponentGroup', 'component_group_id');
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
