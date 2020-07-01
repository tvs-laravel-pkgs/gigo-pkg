<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxCode extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'tax_codes';
	public $timestamps = true;
	protected $fillable = [
		'company_id',
		'code',
		'type_id',
	];

	protected static $excelColumnRules = [
		'Code' => [
			'table_column_name' => 'code',
			'rules' => [
				'required' => [
				],
			],
		],
		'Type Name' => [
			'table_column_name' => 'code',
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

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Tax Name' => $record_data->tax_name,
			'Type Name' => $record_data->type_name,
		];
		return static::saveFromExcelArray($record);
	}

	public static function saveFromExcelArray($record_data) {
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

		if (empty($record_data['Type Name'])) {
			$errors[] = 'Type Name is empty';
		} else {
			$type = Config::where([
				'config_type_id' => 82,
				'name' => $record_data['Type Name'],
			])->first();
			if (!$type) {
				$errors[] = 'Invalid Type Name : ' . $record_data['Type Name'];
			} else {
				$type_id = $type->id;
			}
		}

		$record = Self::firstOrNew([
			'company_id' => $company->id,
			'name' => $record_data['Tax Name'],
		]);
		$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
		if (!$result['success']) {
			return $result;
		}
		$record->type_id = $type_id;
		$record->company_id = $company->id;
		$record->created_by_id = $created_by_id;
		$record->save();
		return [
			'success' => true,
		];
	}
	public static function getList($params = [], $add_default = true, $default_text = 'Select Tax Code') {
		$list = Collect(Self::select([
			'id',
			'code as name',
		])
				->orderBy('code')
				->get());
		if ($add_default) {
			$list->prepend(['id' => '', 'name' => $default_text]);
		}
		return $list;
	}

}
