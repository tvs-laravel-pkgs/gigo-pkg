<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use App\SerialNumberGroup;
use App\VehicleInspectionItemGroup;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleInspectionItem extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	public static $AUTO_GENERATE_CODE = true;
	protected $table = 'vehicle_inspection_items';
	public $timestamps = true;
	protected $fillable = [
		"group_id",
		"code",
		"name",
	];

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
		'Group Name' => [
			'table_column_name' => 'group_id',
			'rules' => [
				'required' => [
				],
				'fk' => [
					'class' => 'App\VehicleInspectionItemGroup',
					'foreign_table_column' => 'name',
				],
			],
		],
	];

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Code' => $record_data->code,
			'Name' => $record_data->name,
			'Group Name' => $record_data->group_name,
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

		if (empty($record_data['Group Name'])) {
			$errors[] = 'Group Name is empty';
		} else {
			$group = VehicleInspectionItemGroup::where([
				'company_id' => $company->id,
				'name' => $record_data['Group Name'],
			])->first();
			if (!$group) {
				$errors[] = 'Invalid Group Name : ' . $record_data['Group Name'];
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
					'group_id' => $group->id,
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
					'group_id' => $group->id,
					'code' => $record_data['Code'],
				]);
			}
		} else {
			$record = static::firstOrNew([
				'group_id' => $group->id,
				'code' => $record_data['Code'],
			]);
		}
		/*$record = self::firstOrNew([
			'group_id' => $group->id,
			'code' => $record_data['Code'],
		]);*/

		$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
		if (!$result['success']) {
			return $result;
		}
		// $record->company_id = $company->id;
		$record->group_id = $group->id;
		$record->created_by_id = $created_by_id;
		$record->save();
		return [
			'success' => true,
		];
	}

	public function getDateOfJoinAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setDateOfJoinAttribute($date) {
		return $this->attributes['date_of_join'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	/*public static function createFromObject($record_data) {

		$errors = [];
		$company = Company::where('code', $record_data->company)->first();
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company);
			return;
		}

		$admin = $company->admin();
		if (!$admin) {
			dump('Default Admin user not found');
			return;
		}

		$type = Config::where('name', $record_data->type)->where('config_type_id', 89)->first();
		if (!$type) {
			$errors[] = 'Invalid Tax Type : ' . $record_data->type;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'name' => $record_data->tax_name,
		]);
		$record->type_id = $type->id;
		$record->created_by_id = $admin->id;
		$record->save();
		return $record;
	}*/

	public static function getList($params = [], $add_default = true, $default_text = 'Select Vehicle Inspection Item') {
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
