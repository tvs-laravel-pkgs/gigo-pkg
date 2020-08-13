<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
// use App\Company;
use Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aggregate extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'aggregates';
	public $timestamps = true;
	protected $fillable =
		["name", "code"];

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
	];

	public static function saveFromObject($record_data) {

		$record = [
			// 'Company Code' => $record_data->company_code,
			'Name' => $record_data->name,
			'Code' => $record_data->code,
		];
		return static::saveFromExcelArray($record);
	}

	public static function saveFromExcelArray($record_data) {
		$errors = [];
		/*$company = Company::where('code', $record_data['Company Code'])->first();
			if (!$company) {
				return [
					'success' => false,
					'errors' => ['Invalid Company : ' . $record_data['Company Code']],
				];
		*/

		if (!isset($record_data['created_by_id'])) {
			// $admin = $company->admin();
			$admin = Auth::user();

			if (!$admin) {
				return [
					'success' => false,
					'errors' => ['Default Admin user not found'],
				];
			}
			$created_by_id = Auth::id(); //$admin->id;
		} else {
			$created_by_id = $record_data['created_by_id'];
		}

		$record = self::firstOrNew([
			// 'company_id' => $company->id,
			'code' => $record_data['Code'],
		]);

		$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
		if (!$result['success']) {
			return $result;
		}

		$record->created_by = $created_by_id;
		$record->save();
		return [
			'success' => true,
		];
	}

}
