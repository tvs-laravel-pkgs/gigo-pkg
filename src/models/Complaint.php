<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;

class Complaint extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'complaints';
	public $timestamps = true;
	protected $fillable =
		["company_id", "code", "name", "group_id", "hours", "kms", "months"]
	;

	protected static $excelColumnRules = [
		/*'Group Code' => [
			'table_column_name' => 'group_id',
			'rules' => [
				'required' => [
				],
				'fk' => [
					'class' => 'App\ComplaintGroup',
					'foreign_table_column' => 'code',
					'check_with_company' => true,
				],
			],
		],*/
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
		'Sub Aggregate Name' => [
			'table_column_name' => 'sub_aggregate_id',
			'rules' => [
				'required' => [
				],
				'fk' => [
					'class' => 'App\SubAggregate',
					'foreign_table_column' => 'name',
					// 'check_with_company' => true,
				],
			],
		],
		//'Hours' => [
		//	'table_column_name' => 'hours',
		//	'rules' => [
		//		'nullable' => [
		//		],
		//		//'unsigned_integer' => [
		//		//	'size' => '8',
		//		//],
		//	],
		//],
		//'KMs' => [
		//	'table_column_name' => 'kms',
		//	'rules' => [
		//		'nullable' => [
		//		],
		//		'unsigned_integer' => [
		//			'size' => '8',
		//		],
		//	],
		//],
		//'Months' => [
		//	'table_column_name' => 'months',
		//	'rules' => [
		//		'nullable' => [
		//		],
		//		//'unsigned_integer' => [
		//		//	'size' => '8',
		//		//],
		//	],
		//],
	];

	// Query Scopes --------------------------------------------------------------

	public function scopeFilterSearch($query, $term) {
		if (strlen($term)) {
			$query->where(function ($query) use ($term) {
				$query->orWhere('code', 'LIKE', '%' . $term . '%');
				$query->orWhere('name', 'LIKE', '%' . $term . '%');
			});
		}
	}

	public function scopeFilterComplaintGroup($query, $complaint_group_id) {
		$query->where('group_id', $complaint_group_id);
		// dd($query->toSql());
	}

	public function scopeFilterSubAggregate($query, $sub_aggregate_id) {
		$query->where('sub_aggregate_id', $sub_aggregate_id);
	}

	public function complaintGroup() {
		return $this->belongsTo('App\ComplaintGroup', 'group_id');
	}

	public function subAggregate() {
		return $this->belongsTo('App\SubAggregate', 'sub_aggregate_id');
	}

	// Static Operations --------------------------------------------------------------

	public static function validate($data, $user) {
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

		/*
			$group = ComplaintGroup::where([
				'company_id' => $admin->company_id,
				'code' => $record_data['Group Code'],
			])->first();
			if (!$group) {
				$errors[] = 'Group not found : ' . $record_data['Group'];
			}
		*/

		if (empty($record_data['Sub Aggregate Name'])) {
			$errors[] = 'Sub Aggregate is empty';
		} else {
			$sub_aggregate = SubAggregate::where([
				// 'company_id' => $admin->company_id,
				'name' => $record_data['Sub Aggregate Name'],
			])->first();
			if ($sub_aggregate == null) {
				$errors[] = 'Sub Aggregate not found : ' . $record_data['Sub Aggregate Name'];
			}
		}

		if (count($errors) > 0) {
			return [
				'success' => false,
				'errors' => $errors,
			];
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			// 'group_id' => $group->id,
			'sub_aggregate_id' => $sub_aggregate->id,
			'code' => $record_data['Code'],
		]);

		$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
		if (!$result['success']) {
			return $result;
		}

		$record->created_by_id = $created_by_id;
		$record->sub_aggregate_id = $sub_aggregate->id;
		$record->name = $record_data['Name'];
		// dd($record);
		$record->save();
		return [
			'success' => true,
		];
	}

	public static function saveFromObject($record_data) {

		$record = [
			'Company Code' => $record_data->company_code,
			// 'Group Code' => $record_data->group_code,
			'Code' => $record_data->code,
			'Name' => $record_data->name,
			//'Hours' => $record_data->hours,
			//'KMs' => $record_data->kms,
			//'Months' => $record_data->months,
			'Sub Aggregate Name' => $record_data->sub_aggregate_name,
		];
		return static::saveFromExcelArray($record);
	}

	public static function getList($params = [], $add_default = true, $default_text = 'Select Complaint Type') {
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
