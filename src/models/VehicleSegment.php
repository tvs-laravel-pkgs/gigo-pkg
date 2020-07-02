<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use App\SerialNumberGroup;
use App\VehicleMake;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;

class VehicleSegment extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'vehicle_segments';
	public $timestamps = true;
	protected $fillable = [
		"id",
		"code",
		"name",
		"vehicle_make_id",
	];
	public static $AUTO_GENERATE_CODE = true;

	protected static $excelColumnRules = [
		'Vehicle Make Name' => [
			'table_column_name' => 'vehicle_make_id',
			'rules' => [
				'required' => [
				],
				'fk' => [
					'class' => 'App\VehicleMake',
					'foreign_table_column' => 'name',
					'check_with_company' => true,
				],
			],
		],
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

	public static function validate($data, $user) {
		$error_messages = [
			'code.required' => 'Code is Required',
			'code.unique' => 'Code already taken',
			'code.min' => 'Code should have minimum 3 Charachers',
			'code.max' => 'Code should have maximum 32 Charachers',
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

	public static function createFromObject($record_data) {
		$errors = [];
		$company = Company::where('code', $record_data->company_code)->first();
		if (!$company) {
			return [
				'success' => false,
				'errors' => ['Invalid Company : ' . $record_data->company],
			];
		}

		$admin = $company->admin();
		if (!$admin) {
			return [
				'success' => false,
				'errors' => ['Default Admin user not found'],
			];
		}

		$validation = Self::validate($original_record, $admin);
		if (count($validation['success']) > 0 || count($errors) > 0) {
			return [
				'success' => false,
				'errors' => array_merge($validation['errors'], $errors),
			];
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'code' => $record_data->code,
		]);
		$record->name = $record_data->name;
		$record->created_by_id = $admin->id;
		$record->save();
		return [
			'success' => true,
		];
	}

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Vehicle Make Name' => $record_data->vehicle_make_name,
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

			if (empty($record_data['Vehicle Make Name'])) {
				$errors[] = 'Vehicle Make Name is empty';
			} else {
				$vehicle_make = VehicleMake::where([
					'company_id' => $company->id,
					'name' => $record_data['Vehicle Make Name'],
				])->first();
				if (!$vehicle_make) {
					$errors[] = 'Invalid Vehicle Make Name : ' . $record_data['Vehicle Make Name'];
				}
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
						'vehicle_make_id' => $vehicle_make->id,

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
						'vehicle_make_id' => $vehicle_make->id,

						'code' => $record_data['Code'],
					]);
				}
			} else {
				$record = static::firstOrNew([
					'company_id' => $company->id,
					'vehicle_make_id' => $vehicle_make->id,

					'code' => $record_data['Code'],
				]);
			}

			/*$record = Self::firstOrNew([
				'vehicle_make_id' => $vehicle_make->id,
				'company_id' => $company->id,
				'name' => $record_data['Name'],
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
