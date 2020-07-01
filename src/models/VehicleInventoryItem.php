<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use App\FieldType;
use App\JobOrder;
use App\SerialNumberGroup;
use Auth;

// use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleInventoryItem extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	public static $AUTO_GENERATE_CODE = true;

	protected $table = 'vehicle_inventory_items';
	public $timestamps = true;
	protected $fillable =
		["id", "company_id", "code", "name", "field_type_id"]
	;
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
		'Field Type Short Name' => [
			'table_column_name' => 'field_type_id',
			'rules' => [
				'required' => [
				],
				'fk' => [
					'class' => 'App\FieldType',
					'foreign_table_column' => 'short_name',
				],
			],
		],
		'Display Order' => [
			'table_column_name' => 'display_order',
			'rules' => [
				'required' => [
				],
			],
		],
	];

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Code' => $record_data->code,
			'Name' => $record_data->name,
			'Field Type Short Name' => $record_data->field_type_short_name,
			'Display Order' => $record_data->display_order,
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

		if (empty($record_data['Field Type Short Name'])) {
			$errors[] = 'Field Type Short Name is empty';
		} else {
			$group = FieldType::where([
				'short_name' => $record_data['Field Type Short Name'],
			])->first();
			if (!$group) {
				$errors[] = 'Invalid Field Type Short Name : ' . $record_data['Field Type Short Name'];
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
						'errors' => $result['error'],
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

		/*$record = self::firstOrNew([
			'company_id' => $company->id,
			'code' => $record_data['Code'],
		]);*/

		$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
		if (!$result['success']) {
			return $result;
		}
		// $record->company_id = $company->id;
		$record->field_type_id = $group->id;
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

	public static function getList($params = [], $add_default = true, $default_text = 'Select Vehicle Inventory Item') {
		$list = Self::select([
			'id',
			'name',
		])
			->where('company_id', Auth::user()->company_id)
			->orderBy('name');
		if (count($params) > 0) {
			foreach ($params as $key => $value) {
				$list->where($key, $value);
			}
		}
		$list = collect($list->get());

		return $list;
	}

	public static function getInventoryList($job_order_id, $params = [], $add_default = true, $default_text = '') {
		$list = Self::select([
			'id',
			'name',
			'field_type_id',
		])
			->where('company_id', Auth::user()->company_id)
			->orderBy('id');
		if (count($params) > 0) {
			foreach ($params as $key => $value) {
				$list->whereIn($key, $value);
			}
		}
		$list = collect($list->get()->keyBy('id'));
		$job_order = JobOrder::find($job_order_id);
		if (!$job_order) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => [
					'Job Order Not Found!',
				],
			]);
		}
		$vehicle_inventory_items = $job_order->vehicleInventoryItem()->orderBy('id')->get()->toArray();

		if (count($vehicle_inventory_items) > 0) {
			foreach ($vehicle_inventory_items as $value) {
				if (isset($list[$value['id']])) {
					$list[$value['id']]->checked = true;
					$list[$value['id']]->is_available = 1;
					if (isset($value['pivot']['remarks'])) {
						$list[$value['id']]->remarks = $value['pivot']['remarks'];
					}
				}
			}
		}

		return $list;

		// $checked_item_ids = [];
		// if (count($vehicle_inventory_items) > 0) {
		// 	foreach ($vehicle_inventory_items as $key => $inv_item) {
		// 		if ($inv_item['pivot']['is_available'] == 1) {
		// 			$checked_item_ids[] = $inv_item['pivot']['vehicle_inventory_item_id'];
		// 		}

		// 	}
		// }
		// $checked_item_ids_unique = array_unique($checked_item_ids);

		// $inventory_items = [];
		// foreach ($list as $key => $item) {
		// 	if (in_array($item->id, $checked_item_ids_unique)) {
		// 		$inventory_items[$key]['id'] = $item->id;
		// 		$inventory_items[$key]['name'] = $item->name;
		// 		$inventory_items[$key]['field_type_id'] = $item->field_type_id;
		// 		$inventory_items[$key]['checked'] = true;
		// 		$inventory_items[$key]['is_available'] = 1;

		// 		if (!empty($vehicle_inventory_items[$key])) {
		// 			$inventory_items[$key]['remarks'] = $vehicle_inventory_items[$key]['pivot']['remarks'] ? $vehicle_inventory_items[$key]['pivot']['remarks'] : '';
		// 		} else {
		// 			$inventory_items[$key]['remarks'] = '';
		// 		}
		// 	} else {
		// 		$inventory_items[$key]['id'] = $item->id;
		// 		$inventory_items[$key]['name'] = $item->name;
		// 		$inventory_items[$key]['field_type_id'] = $item->field_type_id;
		// 		$inventory_items[$key]['checked'] = false;
		// 		// if (!empty($vehicle_inventory_items[$key])) {
		// 		// 	if ($item->field_type_id == 12) {
		// 		// 		$inventory_items[$key]['remarks'] = $vehicle_inventory_items[$key]['pivot']['remarks'] ? $vehicle_inventory_items[$key]['pivot']['remarks'] : 0;
		// 		// 	} else {
		// 		// 		$inventory_items[$key]['remarks'] = $vehicle_inventory_items[$key]['pivot']['remarks'] ? $vehicle_inventory_items[$key]['pivot']['remarks'] : '';
		// 		// 	}
		// 		// } else {
		// 		$inventory_items[$key]['is_available'] = 0;
		// 		if ($item->field_type_id == 12) {
		// 			$inventory_items[$key]['remarks'] = 0;
		// 		} else {
		// 			$inventory_items[$key]['remarks'] = '';
		// 		}
		// 		// }
		// 	}
		// }
		// // dd($inventory_items);
		// //dd($list);
		// return $inventory_items;
	}

}
