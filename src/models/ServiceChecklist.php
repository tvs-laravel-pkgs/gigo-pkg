<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use App\VehicleComponentGroup;
use App\VehicleSegment;
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

	protected static $excelColumnRules = [
		'Vehicle Segment Name' => [
			'table_column_name' => 'segment_id',
			'rules' => [
				'required' => [
				],
				'fk' => [
					'class' => 'App\VehicleSegment',
					'foreign_table_column' => 'name',
					'check_with_company' => true,
				],
			],
		],
		'Component Group Name' => [
			'table_column_name' => 'component_group_id',
			'rules' => [
				'required' => [
				],
				'fk' => [
					'class' => 'App\VehicleComponentGroup',
					'foreign_table_column' => 'name',
					'check_with_company' => true,
				],
			],
		],
		'Maintenance Activity' => [
			'table_column_name' => 'maintenence_activity',
			'rules' => [
				'required' => [
				],
			],
		],
		'Display Order' => [
			'table_column_name' => 'display_order',
			'rules' => [
				'required' => [
				],
				'unsigned_integer' => [
					'size' => '8',
				],
			],
		],
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

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Vehicle Segment Name' => $record_data->vehicle_segment_name,
			'Component Group Name' => $record_data->component_group_name,
			'Maintenance Activity' => $record_data->maintenance_activity,
			'Display Order' => $record_data->display_order,
		];
		return static::saveFromExcelArray($record);
	}

	public static function saveFromExcelArray($record_data) {
		try {
			$errors = [];
			$company = Company::where('code', $record_data['Company Code'])->first();
			if (!$company) {
				return [
					'success' => false,
					'errors' => ['Invalid Company : ' . $record_data['Company Code']],
				];
			}

			if (!isset($record_data['created_by_id'])) {
				$admin = $company->admin();

				if (!$admin) {
					return [
						'success' => false,
						'errors' => ['Default Admin user not found'],
					];
				}
				$created_by_id = $admin->id;
			} else {
				$created_by_id = $record_data['created_by_id'];
			}

			if (empty($record_data['Vehicle Segment Name'])) {
				$errors[] = 'Vehicle Segment Name is empty';
			} else {
				$vehicle_segment = VehicleSegment::where([
					'company_id' => $company->id,
					'name' => $record_data['Vehicle Segment Name'],
				])->first();
				if (!$vehicle_segment) {
					$errors[] = 'Invalid Vehicle Segment Name : ' . $record_data['Vehicle Segment Name'];
				}
			}

			if (empty($record_data['Component Group Name'])) {
				$errors[] = 'Component Group Name is empty';
			} else {
				$component_group = VehicleComponentGroup::where([
					'company_id' => $company->id,
					'name' => $record_data['Component Group Name'],
				])->first();
				if (!$component_group) {
					$errors[] = 'Invalid Component Group Name : ' . $record_data['Component Group Name'];
				}
			}

			if (count($errors) > 0) {
				return [
					'success' => false,
					'errors' => $errors,
				];
			}

			$record = Self::firstOrNew([
				'segment_id' => $vehicle_segment->id,
				'component_group_id' => $component_group->id,
			]);
			$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
			if (!$result['success']) {
				return $result;
			}
			$record->company_id = $company->id;
			$record->created_by_id = $created_by_id;
			$record->save();
			return [
				'success' => true,
			];
		} catch (\Exception $e) {
			return [
				'success' => false,
				'errors' => [
					$e->getMessage(),
				],
			];
		}
	}

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
