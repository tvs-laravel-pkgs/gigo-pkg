<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Abs\StatusPkg\Status;
use App\BaseModel;
use App\Company;
use App\Config;
use App\JobOrder;
use App\Outlet;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bay extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'bays';
	public $timestamps = true;
	protected $fillable =
		["id", "short_name", "outlet_id", "name", "status_id", "job_order_id", "area_type_id"]
	;

	protected static $excelColumnRules = [
		'Outlet Code' => [
			'table_column_name' => 'outlet_id',
			'rules' => [
				'required' => [
				],
				'fk' => [
					'class' => 'App\Outlet',
					'foreign_table_column' => 'code',
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
		'Short Name' => [
			'table_column_name' => 'short_name',
			'rules' => [
				'required' => [
				],
			],
		],
		'Status Name' => [
			'table_column_name' => 'status_id',
			'rules' => [
				'required' => [
				],
				'fk' => [
					'class' => 'App\Config',
					'foreign_table_column' => 'name',
				],
				/*
					'unsigned_integer' => [
						'size' => '10',
				*/
			],
		],
		'Job Order Number' => [
			'table_column_name' => 'job_order_id',
			'rules' => [
				'fk' => [
					'class' => 'App\JobOrder',
					'foreign_table_column' => 'number',
				],
			],
		],
	];

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Outlet Code' => $record_data->outlet_code,
			'Name' => $record_data->name,
			'Short Name' => $record_data->short_name,
			'Status Name' => $record_data->status_name,
			'Job Order Number' => $record_data->job_order_number,
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

			if (empty($record_data['Outlet Code'])) {
				$errors[] = 'Outlet Code is empty';
			} else {
				$outlet = Outlet::where([
					'company_id' => $company->id,
					'code' => $record_data['Outlet Code'],
				])->first();
				if (!$outlet) {
					$errors[] = 'Invalid Outlet Code : ' . $record_data['Outlet Code'];
				} else {
					$outlet_id = $outlet->id;
				}
			}

			if (empty($record_data['Status Name'])) {
				$errors[] = 'Status Name is empty';
			} else {
				$status = Config::where([
					'config_type_id' => 43,
					'name' => $record_data['Status Name'],
				])->first();
				if (!$status) {
					$errors[] = 'Invalid Status Name : ' . $record_data['Status Name'];
				} else {
					$status_id = $status->id;
				}
			}

			$job_order_id = null;
			if (!empty($record_data['Job Order Number'])) {
				$job_order = JobOrder::where([
					'company_id' => $company->id,
					'number' => $record_data['Job Order Number'],
				])->first();
				if (!$job_order) {
					$errors[] = 'Invalid Job Order Number : ' . $record_data['Job Order Number'];
				} else {
					$job_order_id = $job_order->id;
				}
			}
			if (count($errors) > 0) {
				return [
					'success' => false,
					'errors' => $errors,
				];
			}

			$record = Self::firstOrNew([
				'outlet_id' => $outlet->id,
				'short_name' => $record_data['Short Name'],
			]);

			$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
			if (!$result['success']) {
				return $result;
			}

			$record->outlet_id = $outlet_id;
			$record->job_order_id = $job_order_id;
			$record->status_id = $status_id;
			// $record->company_id = $company->id;
			$record->created_by_id = $created_by_id;
			$record->save();
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

	public function status() {
		//issue: wrong relation
		// return $this->belongsTo('Abs\StatusPkg\Status', 'status_id');
		return $this->belongsTo('App\Config', 'status_id');
	}

	public function jobOrder() {
		return $this->belongsTo('App\JobOrder', 'job_order_id');
	}

	public function outlet() {
		return $this->belongsTo('App\Outlet', 'outlet_id');
	}

}
