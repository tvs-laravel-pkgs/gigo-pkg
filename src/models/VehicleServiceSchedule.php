<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use App\SerialNumberGroup;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleServiceSchedule extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'vehicle_service_schedules';
	public $timestamps = true;

	public static $AUTO_GENERATE_CODE = true;

	protected $fillable = [
		"company_id",
		"code",
		"name",
	];

	protected $dates = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	protected $casts = [
	];

	protected static $excelColumnRules = [
		'Code' => [
			'table_column_name' => 'code',
			'rules' => [
				'required' => [
				],
			],
		],
		'Name' => [
			'table_column_name' => 'name',
			'rules' => [
				'required' => [
				],
			],
		],
	];

	public static function relationships($action = '') {
		$relationships = [
			'vehicle_service_schedule_service_types',
			'vehicle_service_schedule_service_types.serviceType',
			'vehicle_service_schedule_service_types.tolerance_km',
			'vehicle_service_schedule_service_types.tolerance_period',
			'vehicle_service_schedule_service_types.parts',
			'vehicle_service_schedule_service_types.repair_orders',
		];

		return $relationships;
	}
	// Getter & Setters --------------------------------------------------------------

	// Relations --------------------------------------------------------------

	public function vehicle_service_schedule_service_types() {
		return $this->hasMany('App\VehicleServiceScheduleServiceType')->orderBy('id');
	}

	public function company() {
		return $this->belongsTo('App\Company');
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
	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Code' => $record_data->code,
			'Name' => $record_data->name,
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

			if (count($errors) > 0) {
				return [
					'success' => false,
					'errors' => $errors,
				];
			}
			if (Self::$AUTO_GENERATE_CODE) {
				if (empty($record_data['Code'])) {
					$record = static::firstOrNew([
						'company_id' => $company->id,
						'name' => $record_data['Name'],
					]);
					$result = SerialNumberGroup::generateNumber(static::$SERIAL_NUMBER_CATEGORY_ID);
					if ($result['success']) {
						$record_data['Code'] = $result['number'];
					} else {
						return [
							'success' => false,
							'errors' => $result['errors'],
						];
					}
				} else {
					$record = static::firstOrNew([
						'company_id' => $company->id,
						'code' => $record_data['Code'],
					]);
				}
			} else {
				$record = static::firstOrNew([
					'company_id' => $company->id,
					'code' => $record_data['Code'],
				]);
			}

			/*$record = Self::firstOrNew([
				'company_id' => $company->id,
				'code' => $record_data['Code'],
			]);*/
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

}
