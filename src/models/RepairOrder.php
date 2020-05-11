<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairOrder extends Model {
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
				$status = [];
				$status['errors'] = [];

				if (empty($record['Type'])) {
					$status['errors'][] = 'Type is empty';
				} else {
					$type = RepairOrderType::where([
						'company_id' => $job->company_id,
						'short_name' => $record['Type'],
					])->first();
					if (!$type) {
						$status['errors'][] = 'Invalid Type';
					}
					
				}

				if (empty($record['Code'])) {
					$status['errors'][] = 'Code is empty';
				} else {
					$code = RepairOrder::where([
						'company_id' => $job->company_id,
						'code' => $record['Code'],
					])->first();
					if ($code) {
						$status['errors'][] = 'Code already taken';
					}
				}

				if (empty($record['Alt Code'])) {
					$status['errors'][] = 'Alt Code is empty';
				} else {
					$alt_code = RepairOrder::where([
						'company_id' => $job->company_id,
						'alt_code' => $record['Alt Code'],
					])->first();
					if ($alt_code) {
						$status['errors'][] = 'Alt Code already taken';
					}
				}

				if (empty($record['Name'])) {
					$status['errors'][] = 'Name is empty';
				} else {
					$name = RepairOrder::where([
						'company_id' => $job->company_id,
						'name' => $record['Name'],
					])->first();
					if ($name) {
						$status['errors'][] = 'Name already taken';
					}
				}

				if (empty($record['UOM'])) {
					$status['errors'][] = 'UOM is empty';
				} else {
					$uom = Uom::where([
						'company_id' => $job->company_id,
						'code' => $record['UOM'],
					])->first();
					if (!$uom) {
						$status['errors'][] = 'Invalid UOM';
					}
				}

				if (empty($record['Skill Level'])) {
					$status['errors'][] = 'UOM is empty';
				} else {
					$skill_level = SkillLevel::where([
						'company_id' => $job->company_id,
						'name' => $record['Skill Leve'],
					])->first();
					if (!$skill_level) {
						$status['errors'][] = 'Invalid Skill Leve';
					}
				}

				if (empty($record['Hours'])) {
					$status['errors'][] = 'Hours is empty';
				}

				if (empty($record['Amount'])) {
					$status['errors'][] = 'Amount is empty';
				}

				if (empty($record['Tax Code'])) {
					$status['errors'][] = 'Tax Code is empty';
				} else {
					$tax_code = TaxCode::where([
						'company_id' => $job->company_id,
						'code' => $record['Tax Code'],
					])->first();
					if (!$tax_code) {
						$status['errors'][] = 'Invalid Tax Code';
					}
				}
			
				if (count($status['errors']) > 0) {
					// dump($status['errors']);
					$original_record['Record No'] = $k + 1;
					$original_record['Error Details'] = implode(',', $status['errors']);
					$all_error_records[] = $original_record;
					$job->incrementError();
					continue;
				}

				DB::beginTransaction();

				$repair_order = RepairOrder::firstOrNew([
					'company_id' => $job->company_id,
				]);

				$repair_order->company_id = $job->company_id;
				$repair_order->type_id = $type->id;
				$repair_order->code = $record['Code'];
				$repair_order->alt_code = $record['Alt Code'];
				$repair_order->name = $record['Name'];
				$repair_order->uom_id = $uom->id;
				$repair_order->skill_level_id = $skill_level->id;
				$repair_order->hours = $record['Hours'];
				$repair_order->amount = $record['Amount'];
				$repair_order->tax_code_id = $tax_code->id;
				$repair_order->created_by_id = $job->created_by_id;
				$repair_order->updated_at = NULL;
				$repair_order->save();
                $message = 'Repair Order added successfully';

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

}
