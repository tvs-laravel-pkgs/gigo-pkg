<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Aggregate;
use App\BaseModel;
use App\Company;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubAggregate extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'sub_aggregates';
	public $timestamps = true;
	protected $fillable =
		["company_id", "name", "aggregate_id"];

	protected static $excelColumnRules = [
		'Aggregate' => [
			'table_column_name' => 'aggregate_id',
			'rules' => [
				'required' => [
				],
				'fk' => [
					'class' => 'App\Aggregate',
					'foreign_table_column' => 'name',
					'check_with_company' => true,
				],
			],
		],
		'Sub Aggregate' => [
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
			'Aggregate' => $record_data->aggregate,
			'Sub Aggregate' => $record_data->sub_aggregate,
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

		if (empty($record_data['Aggregate'])) {
			$errors[] = 'Aggregate is empty';
		} else {
			$aggregate = Aggregate::where([
				'company_id' => $admin->company_id,
				'name' => $record_data['Aggregate'],
			])->first();
			if ($aggregate == null) {
				$errors[] = 'Aggregate not found : ' . $record_data['Aggregate'];
			}
		}

		if (count($errors) > 0) {
			return [
				'success' => false,
				'errors' => $errors,
			];
		}

		// dump($aggregate, $record_data['Sub Aggregate']);
		$record = self::firstOrNew([
			'company_id' => $company->id,
			'aggregate_id' => $aggregate->id,
			'name' => $record_data['Sub Aggregate'],
		]);
		$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);

		if (!$result['success']) {
			return $result;
		}
		$record->aggregate_id = $aggregate->id;
		$record->created_by_id = $created_by_id;
		$record->save();
		return [
			'success' => true,
		];
	}

	public function aggregate() {
		return $this->belongsTo('App\Aggregate', 'aggregate_id');
	}

}
