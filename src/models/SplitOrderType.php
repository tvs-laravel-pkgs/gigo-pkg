<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use App\Config;
use App\SerialNumberGroup;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SplitOrderType extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'split_order_types';
	public $timestamps = true;
	public static $AUTO_GENERATE_CODE = true;

	protected $fillable = [
		"id",
		"company_id",
		"code",
		"name",
		"paid_by_id",
		"claim_category_id",
	];

	public function paidBy() {
		return $this->belongsTo('App\Config', 'paid_by_id');
	}

	protected static $excelColumnRules = [
		'Name' => [
			'table_column_name' => 'name',
			'rules' => [
				'required' => [
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
		'Paid By' => [
			'table_column_name' => 'claim_category_id',
			'rules' => [
				'nullable' => [
				],
				'fk' => [
					'class' => 'App\Config',
					'foreign_table_column' => 'name',
					'additional_conditions' => [
						'config_type_id' => 400,
					],
				],
			],
		],
		'Claim Category' => [
			'table_column_name' => 'paid_by_id',
			'rules' => [
				'nullable' => [
				],
				'fk' => [
					'class' => 'App\Config',
					'foreign_table_column' => 'name',
					'additional_conditions' => [
						'config_type_id' => 405,
					],
				],
			],
		],
	];

	public function getDateOfJoinAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setDateOfJoinAttribute($date) {
		return $this->attributes['date_of_join'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Code' => $record_data->code,
			'Name' => $record_data->name,
			'Paid By' => $record_data->paid_by,
			'Claim Category' => $record_data->claim_category,
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

			/*if (count($errors) > 0) {
				return [
					'success' => false,
					'errors' => $errors,
				];
			}*/
			$claim_category_id = null;

			if (!empty($record_data['Claim Category'])) {
				$claim_category = Config::where([
					'config_type_id' => 405,
					'name' => $record_data['Claim Category'],
				])->first();
				if (!$claim_category) {
					$errors[] = 'Invalid Claim Category : ' . $record_data['Claim Category'];
				} else {
					$claim_category_id = $claim_category->id;
				}
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

			/*
				$record = Self::firstOrNew([
					'company_id' => $company->id,
					'code' => $record_data['Code'],
			*/

			$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
			if (!$result['success']) {
				return $result;
			}

			$record->claim_category_id = $claim_category_id;
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

	public static function getList($params = [], $add_default = true, $default_text = 'Select Split Order Type') {
		$list = Collect(Self::select([
			'id',
			'name',
		])
				->orderBy('name')
				->get());
		if ($add_default) {
			$list->prepend(['id' => '', 'name' => $default_text]);
		}
		return $list;
	}

}
