<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOrderRepairOrder extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'job_order_repair_orders';
	public $timestamps = true;
	protected $fillable =
		["job_order_id", "repair_order_id", "is_recommended_by_oem", "is_customer_approved", "split_order_type_id", "qty", "amount", "failure_date", "status_id", "remarks", "observation", "action_taken", "is_free_service"]
	;

	public function getFailureDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setFailureDateAttribute($date) {
		return $this->attributes['failure_date'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}
	public function repairOrder() {
		return $this->belongsTo('Abs\GigoPkg\RepairOrder', 'repair_order_id');
	}

	public function repairOrderMechanics() {
		return $this->hasMany('Abs\GigoPkg\RepairOrderMechanic', 'job_order_repair_order_id');
	}

	public function splitOrderType() {
		return $this->belongsTo('App\SplitOrderType', 'split_order_type_id');
	}

	public function repairOrderMechanic() {
		return $this->belongsTo('App\RepairOrderMechanic', 'job_order_repair_order_id');
	}

	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}

	public function fault() {
		return $this->belongsTo('App\Fault', 'fault_id');
	}

	public function complaint() {
		return $this->belongsTo('App\Complaint', 'complaint_id');
	}

	public function labourReviewAttachment() {
		return $this->hasMany('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 10096);
	}

	public function customerVoice() {
		return $this->belongsTo('App\CustomerVoice', 'customer_voice_id');
	}

	public function taxes() {
		return $this->belongsToMany('App\Tax', 'job_order_repair_order_tax', 'job_order_repair_order_id', 'tax_id')->withPivot(['percentage', 'amount']);
	}

	public function jobOrder() {
		return $this->belongsTo('Abs\GigoPkg\JobOrder', 'job_order_id');
	}

	public static function createFromObject($record_data) {

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
	}

	public static function getList($params = [], $add_default = true, $default_text = 'Select Job Order Repair Order') {
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
