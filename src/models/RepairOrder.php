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
use Auth;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairOrder extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'repair_orders';
	public $timestamps = true;
	protected $fillable = [
		'type_id',
		'code',
		'alt_code',
		'name',
		'skill_level_id',
		'hours',
		'amount',
		'tax_code_id',
		'uom_id',
	];

	public static function relationships($action = '') {
		$relationships = [
			'skillLevel',
		];

		return $relationships;
	}

	// Relationships --------------------------------------------------------------

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

	public static function importFromArray($record) {
		$status = [];
		$status['errors'] = [];

		// $validation = Self::validate($original_record, $admin);
		// if (count($validation['success']) > 0 || count($errors) > 0) {
		// 	return [
		// 		'success' => false,
		// 		'errors' => array_merge($validation['errors'], $errors),
		// 	];
		// }

		$type_id = null;
		if (!empty($record['Type'])) {
			$type = RepairOrderType::where([
				'company_id' => $record['company_id'],
				'short_name' => $record['Type'],
			])->first();
			if (!$type) {
				$status['errors'][] = 'Invalid Type';
			} else {
				$type_id = $type->id;
			}
		}

		if (empty($record['Code'])) {
			$status['errors'][] = 'Code is empty';
		} else {
			$code = RepairOrder::where([
				'company_id' => $record['company_id'],
				'code' => $record['Code'],
			])->first();
			// if ($code) {
			// 	$status['errors'][] = 'Code already taken';
			// }
		}

		if (!empty($record['Alt Code'])) {
			$alt_code = RepairOrder::where([
				'company_id' => $record['company_id'],
				'alt_code' => $record['Alt Code'],
			])->first();
			/*if ($alt_code) {
						$status['errors'][] = 'Alt Code already taken';
					}*/
		}

		if (empty($record['Name'])) {
			$status['errors'][] = 'Name is empty';
		} else {
			$name = RepairOrder::where([
				'company_id' => $record['company_id'],
				'name' => $record['Name'],
			])->first();
			/*if ($name) {
						$status['errors'][] = 'Name already taken';
					}*/
		}

		$uom_id = null;
		if (!empty($record['UOM'])) {
			$uom = Uom::where([
				'company_id' => $record['company_id'],
				'code' => $record['UOM'],
			])->first();
			if (!$uom) {
				$status['errors'][] = 'Invalid UOM';
			} else {
				$uom_id = $uom->id;
			}
		}

		$skill_level_id = null;
		if (!empty($record['Skill Level'])) {
			$skill_level = SkillLevel::where([
				'company_id' => $record['company_id'],
				'short_name' => $record['Skill Level'],
			])->first();
			if (!$skill_level) {
				$status['errors'][] = 'Invalid Skill Leve';
			} else {
				$skill_level_id = $skill_level->id;
			}
		}

		if (empty($record['Hours'])) {
			$status['errors'][] = 'Hours is empty';
		}

		if (empty($record['Amount'])) {
			$status['errors'][] = 'Amount is empty';
		}

		$tax_code_id = null;
		if (!empty($record['Tax Code'])) {
			$tax_code = TaxCode::where([
				'company_id' => $record['company_id'],
				'code' => $record['Tax Code'],
			])->first();
			if (!$tax_code) {
				$status['errors'][] = 'Invalid Tax Code';
			} else {
				$tax_code_id = $tax_code->id;
			}
		}

		if (count($status['errors']) > 0) {
			return [
				'success' => false,
				'errors' => $status['errors'],
			];
		}

		$repair_order = RepairOrder::firstOrNew([
			'type_id' => $type_id,
			'code' => $record['Code'],
			'company_id' => $record['company_id'],
		]);

		$repair_order->company_id = $record['company_id'];
		$repair_order->type_id = $type_id;
		$repair_order->code = $record['Code'];
		$repair_order->alt_code = $record['Alt Code'];
		$repair_order->name = $record['Name'];
		$repair_order->uom_id = $uom_id;
		$repair_order->skill_level_id = $skill_level_id;
		$repair_order->hours = $record['Hours'];
		$repair_order->amount = $record['Amount'];
		$repair_order->tax_code_id = $tax_code_id;
		$repair_order->created_by_id = $record['created_by_id'];
		$repair_order->save();

		return [
			'success' => true,
		];
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
				$result = self::importFromArray($record);
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

	public static function createFromObject($record_data) {
		$errors = [];
		$company = Company::where('code', $record_data->company_code)->first();
		if (!$company) {
			return [
				'success' => false,
				'errors' => ['Invalid Company : ' . $record_data->company],
			];
		}

		$admin = $company->admin();
		if (!$admin) {
			return [
				'success' => false,
				'errors' => ['Default Admin user not found'],
			];
		}

		$record = [
			'company_id' => $company->id,
			'Type' => $record_data->type,
			'Code' => $record_data->code,
			'Name' => $record_data->name,
			'Hours' => $record_data->hours,
			'Skill Level' => $record_data->skill_level,
			'Amount' => $record_data->amount,
			'Alt Code' => $record_data->alt_code,
			'UOM' => $record_data->uom,
			'Tax Code' => $record_data->tax_code,
			'created_by_id' => $admin->id,
		];
		return self::importFromArray($record);
	}

	public static function getList($params = [], $add_default = true, $default_text = 'Select Repair Order') {
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
