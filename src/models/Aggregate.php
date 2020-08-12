<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aggregate extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'aggregates';
	public $timestamps = true;
	protected $fillable =
		["company_id", "name"];

	protected static $excelColumnRules = [
		'Aggregate Name' => [
			'table_column_name' => 'name',
			'rules' => [
				'required' => [
				],
			],
		],
	];

	public static function saveFromObject($record_data) {

		$record = [
			'Company Code' => $record_data->company_code,
			'Aggregate Name' => $record_data->aggregate_name,
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

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'name' => $record_data['Aggregate Name'],
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
	}

}
