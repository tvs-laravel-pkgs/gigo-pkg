<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;

class WarrantyJobOrderRequest extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'warranty_job_order_requests';
	public $timestamps = true;
	protected $fillable = [
		"id",
		"number",
		"job_order_id",
		"authorization_number",
		"failure_date",
		"has_warranty",
		"has_amc",
		"unit_serial_number",
		"complaint_id",
		"fault_id",
		"supplier_id",
		"primary_segment_id",
		"secondary_segment_id",
		"has_goodwill",
		"operating_condition_id",
		"normal_road_condition_id",
		"failure_road_condition_id",
		"load_carried_type_id",
		"load_carried",
		"load_range_id",
		"load_at_failure",
		"last_lube_changed",
		"terrain_at_failure_id",
		"reading_type_id",
		"runs_per_day",
		"failed_at",
		"complaint_reported",
		"failure_observed",
		"investigation_findings",
		"cause_of_failure",
		"status_id",
	];

	// Relationships --------------------------------------------------------------

	public function jobOrder() {
		return $this->belongsTo('App\JobOrder');
	}

	public function complaint() {
		return $this->belongsTo('App\Complaint');
	}

	public function fault() {
		return $this->belongsTo('App\Fault');
	}

	public function supplier() {
		return $this->belongsTo('App\PartSupplier', 'supplier_id');
	}

	public function primarySegment() {
		return $this->belongsTo('App\VehiclePrimatyApplication', 'primary_segment_id');
	}

	public function secondarySegment() {
		return $this->belongsTo('App\VehicleSecondaryApplication', 'secondary_segment_id');
	}

	public function operatingCondition() {
		return $this->belongsTo('App\Config', 'operating_condition_id');
	}

	public function normalRoadCondition() {
		return $this->belongsTo('App\Config', 'normal_road_condition_id');
	}

	public function failureRoadCondition() {
		return $this->belongsTo('App\Config', 'failure_road_condition_id');
	}

	public function loadCarriedType() {
		return $this->belongsTo('App\Config', 'load_carried_type_id');
	}

	public function loadRange() {
		return $this->belongsTo('App\Config', 'load_range_id');
	}

	public function terrainAtFailure() {
		return $this->belongsTo('App\Config', 'terrain_at_failure_id');
	}

	public function readingType() {
		return $this->belongsTo('App\Config', 'reading_type_id');
	}

	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}

	// Query Scopes --------------------------------------------------------------

	public function scopeFilterSearch($query, $term) {
		if (strlen($term)) {
			$query->where(function ($query) use ($term) {
				$query->orWhere('number', 'LIKE', '%' . $term . '%');
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

		$validation = Self::validate($original_record, $admin);
		if (count($validation['success']) > 0 || count($errors) > 0) {
			return [
				'success' => false,
				'errors' => array_merge($validation['errors'], $errors),
			];
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'code' => $record_data->code,
		]);
		$record->name = $record_data->name;
		$record->created_by_id = $admin->id;
		$record->save();
		return [
			'success' => true,
		];
	}

	public static function getList($params = [], $add_default = true, $default_text = 'Select Fault Type') {
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
