<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use App\ServiceType;
use App\VehicleServiceSchedule;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleServiceScheduleServiceType extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'vehicle_service_schedule_service_types';
	public $timestamps = true;

	protected $fillable = [
		"vehicle_service_schedule_id",
		"service_type_id",
		"is_free",
		"km_reading",
		"km_tolerance",
		"km_tolerance_type_id",
		"period",
		"period_tolerance",
		"period_tolerance_type_id",
	];

	protected $dates = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	protected $casts = [
		'is_free' => 'bool',
	];

	protected static $excelColumnRules = [
		'Vehicle Service Schedule Name' => [
			'table_column_name' => 'vehicle_service_schedule_id',
			'rules' => [
				'required' => [
				],
				'fk' => [
					'class' => 'App\VehicleServiceSchedule',
					'foreign_table_column' => 'name',
					'check_with_company' => true,
				],
			],
		],
		'Service Type Name' => [
			'table_column_name' => 'service_type_id',
			'rules' => [
				'required' => [
				],
				'fk' => [
					'class' => 'App\ServiceType',
					'foreign_table_column' => 'name',
					'check_with_company' => true,
				],
			],
		],
		'Is Free' => [
			'table_column_name' => 'is_free',
			'rules' => [
				'boolean' => [
				],
			],
		],
		'KM Reading' => [
			'table_column_name' => 'km_reading',
			'rules' => [
				// 'required' => [
				// ],
				'unsigned_integer' => [
					'size' => '10',
				],
			],
		],
		'KM Tolerance' => [
			'table_column_name' => 'km_tolerance',
			'rules' => [
				'required' => [
				],
				'unsigned_integer' => [
					'size' => '8',
				],
			],
		],
		'KM Tolerance Type Name' => [
			'table_column_name' => 'km_tolerance_type_id',
			'rules' => [
				'required' => [
				],
				'fk' => [
					'class' => 'App\Config',
					'foreign_table_column' => 'name',
				],
			],
		],
		'Period' => [
			'table_column_name' => 'period',
			'rules' => [
				'required' => [
				],
				'unsigned_integer' => [
					'size' => '8',
				],
			],
		],
		'Period Tolerance' => [
			'table_column_name' => 'period_tolerance',
			'rules' => [
				'required' => [
				],
				'unsigned_integer' => [
					'size' => '8',
				],
			],
		],
		'Period Tolerance Type Name' => [
			'table_column_name' => 'period_tolerance_type_id',
			'rules' => [
				'required' => [
				],
				'fk' => [
					'class' => 'App\Config',
					'foreign_table_column' => 'name',
				],
			],
		],
	];

	// Getter & Setters --------------------------------------------------------------

	// Relations --------------------------------------------------------------

	public function vehicleServiceSchedule() {
		return $this->belongsTo('App\VehicleServiceSchedule');
	}

	public function serviceType() {
		return $this->belongsTo('App\ServiceType');
	}

	public function createdBy() {
		return $this->belongsTo('App\User', 'created_by_id');
	}

	public function updatedBy() {
		return $this->belongsTo('App\User', 'updated_by_id');
	}

	public function deletedBy() {
		return $this->belongsTo('App\User', 'deleted_by_id');
	}

	// Static Operations --------------------------------------------------------------

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Vehicle Service Schedule Name' => $record_data->vehicle_service_schedule_name,
			'Service Type Name' => $record_data->service_type_name,
			'Is Free' => $record_data->is_free,
			'KM Reading' => $record_data->km_reading,
			'KM Tolerance' => $record_data->km_tolerance,
			'KM Tolerance Type Name' => $record_data->km_tolerance_type_name,
			'Period' => $record_data->period,
			'Period Tolerance' => $record_data->period_tolerance,
			'Period Tolerance Type Name' => $record_data->period_tolerance_type_name,
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

			if (empty($record_data['Vehicle Service Schedule Name'])) {
				$errors[] = 'Vehicle Service Schedule Name is empty';
			} else {
				$vehicle_service_schedule = VehicleServiceSchedule::where([
					'company_id' => $company->id,
					'name' => $record_data['Vehicle Service Schedule Name'],
				])->first();
				if (!$vehicle_service_schedule) {
					$errors[] = 'Invalid Vehicle Service Schedule Name : ' . $record_data['Vehicle Service Schedule Name'];
				}
			}

			if (empty($record_data['Service Type Name'])) {
				$errors[] = 'Service Type Name is empty';
			} else {
				$service_type = ServiceType::where([
					'company_id' => $company->id,
					'name' => $record_data['Service Type Name'],
				])->first();
				if (!$service_type) {
					$errors[] = 'Invalid Service Type Name : ' . $record_data['Service Type Name'];
				}
			}

			if (count($errors) > 0) {
				return [
					'success' => false,
					'errors' => $errors,
				];
			}

			$record = Self::firstOrNew([
				'vehicle_service_schedule_id' => $vehicle_service_schedule->id,
				'service_type_id' => $service_type->id,
			]);
			$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
			if (!$result['success']) {
				return $result;
			}
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
