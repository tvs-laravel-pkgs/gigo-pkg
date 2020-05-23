<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOrder extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'job_orders';
	public $timestamps = true;
	protected $fillable =
		["company_id", "gate_log_id", "number", "type_id", "quote_type_id", "service_type_id", "outlet_id", "contact_number", "driver_license_expiry_date", "insurance_expiry_date", "voc", "is_road_test_required", "road_test_done_by_id", "road_test_performed_by_id", "road_test_report", "warranty_expiry_date", "ewp_expiry_date", "status_id", "estimated_delivery_date", "estimation_type_id", "minimum_payable_amount", "floor_advisor_id"]
	;

	public function getDriverLicenseExpiryDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setDriverLicenseExpiryDateAttribute($date) {
		return $this->attributes['driver_license_expiry_date'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}
	public function getInsuranceExpiryDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setInsuranceExpiryDateAttribute($date) {
		return $this->attributes['insurance_expiry_date'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function getWarrantyExpiryDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setWarrantyExpiryDateAttribute($date) {
		return $this->attributes['warranty_expiry_date'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}
	public function getEwpExpiryDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setEwpExpiryDateAttribute($date) {
		return $this->attributes['ewp_expiry_date'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function customerVoice() {
		return $this->belongsToMany('App\CustomerVoice', 'job_order_customer_voice', 'job_order_id', 'customer_voice_id');
	}

	public function jobOrderVehicleInspectionItem() {
		return $this->belongsToMany('App\VehicleInspectionItem', 'job_order_vehicle_inspection_item', 'job_order_id', 'vehicle_inspection_item_id')->withPivot(['status_id']);
	}

	public function vehicleInventoryItem() {
		return $this->belongsToMany('App\VehicleInventoryItem', 'job_order_vehicle_inventory_item', 'job_order_id', 'vehicle_inventory_item_id')->withPivot(['is_available', 'remarks']);
	}

	//issue: naming
	// public function jobOrderPart() {
	// 	return $this->hasMany('App\JobOrderPart');
	// }
	//issue: naming
	// public function jobOrderRepairOrder() {
	// 	return $this->hasMany('App\JobOrderRepairOrder');
	// }

	//issue: naming
	// public function getEomRecomentation() {
	// 	return $this->hasMany('App\JobOrderRepairOrder', 'job_order_id', 'id');
	// }
	//issue: naming
	// public function getAdditionalRotAndParts() {
	// 	return $this->hasMany('App\JobOrderPart', 'job_order_id', 'id');
	// }

	public function jobOrderRepairOrders() {
		return $this->hasMany('App\JobOrderRepairOrder', 'job_order_id');
	}

	public function jobOrderParts() {
		return $this->hasMany('App\JobOrderPart', 'job_order_id');
	}

	public function gateLog() {
		return $this->belongsTo('App\GateLog', 'gate_log_id');
	}

	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}

	public function serviceOrederType() {
		return $this->belongsTo('Abs\GigoPkg\ServiceOrderType', 'type_id')->where('company_id', Auth::user()->company_id);
	}

	public function quoteType() {
		return $this->belongsTo('App\QuoteType', 'quote_type_id')->where('company_id', Auth::user()->company_id);
	}

	public function serviceType() {
		return $this->belongsTo('App\ServiceType', 'service_type_id')->where('company_id', Auth::user()->company_id);
	}

	public function roadTestDoneBy() {
		return $this->belongsTo('App\Config', 'road_test_done_by_id');
	}

	public function roadTestPreferedBy() {
		return $this->belongsTo('App\User', 'road_test_performed_by_id')->where('company_id', Auth::user()->company_id);
	}

	public function expertDiagnosisReportBy() {
		return $this->belongsTo('App\User', 'expert_diagnosis_report_by_id')->where('company_id', Auth::user()->company_id);
	}

	public function floorAdviser() {
		return $this->belongsTo('App\Employee', 'floor_adviser_id')->where('company_id', Auth::user()->company_id);
	}

	public function jobCard() {
		return $this->hasOne('App\JobCard', 'job_order_id');
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

	public static function getList($params = [], $add_default = true, $default_text = 'Select Job Order') {
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
