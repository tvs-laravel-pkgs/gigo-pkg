<?php

namespace Abs\GigoPkg;

use Abs\GigoPkg\CustomerVoiceRot;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerVoice extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'customer_voices';
	public $timestamps = true;
	protected $fillable = [
		"company_id",
		"code",
		"name",
		"repair_order_id",
		"lv_main_type_id",
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
		'ROT Code' => [
			'table_column_name' => 'repair_order_id',
			'rules' => [
				'nullable' => [
				],
				'fk' => [
					'class' => 'App\RepairOrder',
					'foreign_table_column' => 'code',
					'check_with_company' => true,
				],
			],
		],
	];

	// Getter & Setters --------------------------------------------------------------

	// Relations --------------------------------------------------------------

	public function repair_order() {
		return $this->belongsTo('App\RepairOrder');
	}

	public function repairOrders() {
		return $this->belongsToMany('App\RepairOrder', 'customer_voice_repair_orders', 'customer_voice_id', 'repair_order_id');
	}

	// Static Operations --------------------------------------------------------------

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Code' => $record_data->code,
			'Name' => $record_data->name,
			'ROT Code' => $record_data->rot_code,
			'Lv Main Type' => $record_data->lv_main_type,
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

			//LV Main Type
			$lv_main_type = LvMainType::where('name', $record_data['Lv Main Type'])->where('company_id', $company->id)->select('id')->first();

			$lv_main_type_id = NULL;
			if ($lv_main_type) {
				$lv_main_type_id = $lv_main_type->id;
			}

			$record = Self::firstOrNew([
				'company_id' => $company->id,
				'code' => $record_data['Code'],
				'name' => $record_data['Name'],
				'lv_main_type_id' => $lv_main_type_id,
			]);

			$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
			if (!$result['success']) {
				return $result;
			}
			$record->created_by_id = $created_by_id;
			$record->save();

			//Repair Order
			$repair_order = RepairOrder::where('code', $record_data['ROT Code'])->where('company_id', $company->id)->select('id')->first();

			//Save ROTs
			if ($repair_order) {
				//Check Rot already added or not
				$voc_rot = CustomerVoiceRot::firstOrNew([
					'customer_voice_id' => $record->id,
					'repair_order_id' => $repair_order->id,
				]);
				$voc_rot->save();
			}

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

	public static function getList($params = [], $add_default = true, $default_text = 'Select Customer Voice') {
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
