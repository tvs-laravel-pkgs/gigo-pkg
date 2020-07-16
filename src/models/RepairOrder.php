<?php

namespace Abs\GigoPkg;

use Abs\EmployeePkg\SkillLevel;
use Abs\GigoPkg\RepairOrderType;
use Abs\GigoPkg\TaxCode;
use Abs\HelperPkg\Traits\SeederTrait;
use Abs\ImportCronJobPkg\ImportCronJob;
use Abs\UomPkg\Uom;
use App\BaseModel;
use App\Company;
use App\Part;
use Auth;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairOrder extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'repair_orders';
	public $timestamps = true;
	protected $fillable = [
		'company_id',
		'type_id',
		'code',
		'alt_code',
		'name',
		'category_id',
		'skill_level_id',
		'hours',
		'amount',
		'claim_amount',
		'maximum_claim_amount',
		'tax_code_id',
		'uom_id',
	];

	protected static $excelColumnRules = [
		'Group Code' => [
			'table_column_name' => 'type_id',
			'rules' => [
				'nullable' => [
				],
				'fk' => [
					'class' => 'App\RepairOrderType',
					'foreign_table_column' => 'short_name',
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
		'Category Name' => [
			'table_column_name' => 'category_id',
			'rules' => [
				'nullable' => [
				],
				'fk' => [
					'class' => 'App\Config',
					'foreign_table_column' => 'name',
					'check_with_company' => true,
					'additional_conditions' => [
						'entity_type_id' => 306,
					],
				],
			],
		],
		'Hours' => [
			'table_column_name' => 'hours',
			'rules' => [
				'nullable' => [
				],
				'unsigned_integer' => [
					'size' => '8',
				],
			],
		],
		'Skill Level Short Name' => [
			'table_column_name' => 'skill_level_id',
			'rules' => [
				'nullable' => [],
				'fk' => [
					'class' => 'App\SkillLevel',
					'foreign_table_column' => 'short_name',
					'self_table_column' => 'skill_level_id',
					'check_with_company' => true,
				],
			],
		],
		'Amount' => [
			'table_column_name' => 'amount',
			'rules' => [
				'required' => [
				],
				'unsigned_decimal' => [
					'size' => '12,2',
				],
			],
		],
		'Claim Amount' => [
			'table_column_name' => 'claim_amount',
			'rules' => [
				'required' => [
				],
				'unsigned_decimal' => [
					'size' => '12,2',
				],
			],
		],
		'Maximum Claim Amount' => [
			'table_column_name' => 'maximum_claim_amount',
			'rules' => [
				'required_if' => [
					'claim_amount',
				],
				'nullable' => [
				],
				'unsigned_decimal' => [
					'size' => '12,2',
				],
			],
		],
	];

	// Relationships --------------------------------------------------------------

	public static function relationships($action = '') {
		$relationships = [
			'skillLevel',
			'category',
			'taxCode',
			'taxCode.taxes',
		];

		return $relationships;
	}

	public function category() {
		return $this->belongsTo('App\Config', 'category_id');
	}

	public function repairOrderType() {
		return $this->belongsTo('App\RepairOrderType', 'type_id');
	}

	public function uom() {
		return $this->belongsTo('App\Uom', 'uom_id');
	}

	public function taxCode() {
		return $this->belongsTo('Abs\TaxPkg\TaxCode', 'tax_code_id');
	}

	public function skillLevel() {
		return $this->belongsTo('App\SkillLevel', 'skill_level_id');
	}

	public function campaigns() {
		return $this->belongsToMany('App\Campaign', 'compaign_repair_order', 'repair_order_id', 'compaign_id')->withPivot(['amount']);
	}

	// Query Scopes --------------------------------------------------------------

	public function scopeFilterSearch($query, $term) {
		if (strlen($term)) {
			$query->where(function ($query) use ($term) {
				$query->orWhere('code', 'LIKE', '%' . $term . '%');
				$query->orWhere('name', 'LIKE', '%' . $term . '%');
				$query->orWhere('alt_code', 'LIKE', '%' . $term . '%');
			});
		}
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

	public static function roList($id) {
		$list = Collect(Self::select([
			'id',
			'name',
		])
				->where('type_id', $id)
				->where('company_id', Auth::user()->company_id)
				->orderBy('name')
				->get());
		$list->prepend(['id' => '', 'name' => 'Select Rot']);
		return $list;
	}

	public static function importFromExcel($job) {

		try {
			$response = ImportCronJob::getRecordsFromExcel($job, 'N');
			$rows = $response['rows'];
			$header = $response['header'];

			$all_error_records = [];
			foreach ($rows as $k => $row) {
				$record = [];
				foreach ($header as $key => $column) {
					if (!$column) {
						continue;
					} else {
						$record[$column] = trim($row[$key]);
					}
				}
				$original_record = $record;
				$record['company_id'] = $job->company_id;
				$record['created_by_id'] = $job->created_by_id;
				$result = static::saveFromExcelArray($record);
				if (!$result['success']) {
					$original_record['Record No'] = $k + 1;
					$original_record['Error Details'] = implode(',', $result['errors']);
					$all_error_records[] = $original_record;
					$job->incrementError();
					continue;
				}

				$job->incrementNew();

				DB::commit();
				//UPDATING PROGRESS FOR EVERY FIVE RECORDS
				if (($k + 1) % 5 == 0) {
					$job->save();
				}
			}

			//COMPLETED or completed with errors
			$job->status_id = $job->error_count == 0 ? 7202 : 7205;
			$job->save();

			ImportCronJob::generateImportReport([
				'job' => $job,
				'all_error_records' => $all_error_records,
			]);

		} catch (\Throwable $e) {
			$job->status_id = 7203; //Error
			$job->error_details = 'Error:' . $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(); //Error
			$job->save();
			dump($job->error_details);
		}

	}

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Group Code' => $record_data->group_code,
			'Code' => $record_data->code,
			'Name' => $record_data->name,
			'Category Name' => $record_data->category_name,
			'Hours' => $record_data->hours,
			'Skill Level Short Name' => $record_data->skill_level_short_name,
			'Amount' => $record_data->amount,
			'Claim Amount' => $record_data->claim_amount,
			'Maximum Claim Amount' => $record_data->maximum_claim_amount,
			'Alt Code' => $record_data->alt_code,
			'UOM Short Name' => $record_data->uom_short_name,
			'Tax Code' => $record_data->tax_code,
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

		$type_id = null;
		if (!empty($record_data['Group Code'])) {
			$type = RepairOrderType::where([
				// 'company_id' => $record['company_id'],
				'company_id' => $company->id,
				'short_name' => $record_data['Group Code'],
			])->first();
			if (!$type) {
				$errors[] = 'Invalid Group Code';
			} else {
				$type_id = $type->id;
			}
		}

		if (empty($record_data['Amount'])) {
			$errors[] = 'Amount is empty';
		} else {
			$amount = $record_data['Amount'];
		}

		if (count($errors) > 0) {
			return [
				'success' => false,
				'errors' => $errors,
				// 'errors' => $status['errors'],
			];
		}

		$record = Self::firstOrNew([
			'company_id' => $company->id,
			// 'type_id' => $type_id,
			'code' => $record_data['Code'],
		]);
		$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
		if (!$result['success']) {
			return $result;
		}
		$record->amount = $amount;
		$record->created_by_id = $created_by_id;
		$record->save();
		return [
			'success' => true,
		];
	}

	public static function getList($params = [], $add_default = true, $default_text = 'Select Repair Order') {
		$list = Collect(Self::select([
			'id',
			'name',
			'code',
		])
				->orderBy('name')
				->get());
		if ($add_default) {
			$list->prepend(['id' => '', 'name' => $default_text]);
		}
		return $list;
	}
	public static function mapParts($records) {
		foreach ($records as $key => $record_data) {
			try {
				if (!$record_data->company_code) {
					continue;
				}
				$record = self::mapPart($record_data);
			} catch (Exception $e) {
				dd($e);
			}
		}
	}

	public function parts() {
		return $this->belongsToMany('App\Part', 'repair_order_part', 'repair_order_id', 'part_id');
	}

	public static function mapPart($record_data) {
		$company = Company::where('code', $record_data['company_code'])->first();

		$errors = [];
		$record = RepairOrder::where('code', $record_data['repair_order_code'])->where('company_id', $company->id)->first();
		if (!$record) {
			$errors[] = 'Invalid Repair Order : ' . $record_data['repair_order_code'];
		}

		$part = Part::where('code', $record_data['part_code'])->where('company_id', $company->id)->first();
		if (!$part) {
			$errors[] = 'Invalid Part : ' . $record_data['part_code'];
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}
		$record->parts()->syncWithoutDetaching([$part->id]);
		// return $record;

	}
}
