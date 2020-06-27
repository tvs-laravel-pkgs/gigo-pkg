<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairOrderType extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'repair_order_types';
	public $timestamps = true;
	protected $fillable =
		["id", "company_id", "short_name", "name"]
	;

	protected static $excelColumnRules = [
		'Short Name' => [
			'table_column_name' => 'short_name',
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
		'Description' => [
			'table_column_name' => 'description',
			'rules' => [
				'nullable' => [
				],
			],
		],
	];

	// Getter & Setters --------------------------------------------------------------

	public function getDateOfJoinAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setDateOfJoinAttribute($date) {
		return $this->attributes['date_of_join'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	// Relationships --------------------------------------------------------------

	// Static operations --------------------------------------------------------------

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Short Name' => $record_data->short_name,
			'Name' => $record_data->name,
			'Description' => $record_data->description,
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

		if (count($errors) > 0) {
			return [
				'success' => false,
				'errors' => $errors,
			];
		}

		$record = Self::firstOrNew([
			'company_id' => $company->id,
			'short_name' => $record_data['Short Name'],
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

	public static function getList($params = [], $add_default = true, $default_text = 'Select Repair Order Type') {
		$list = Collect(Self::select([
			'id',
			'name',
		])
				->orderBy('name')
				->where('company_id', Auth::user()->company_id)
				->get());
		if ($add_default) {
			$list->prepend(['id' => '', 'name' => $default_text]);
		}
		return $list;
	}

}
