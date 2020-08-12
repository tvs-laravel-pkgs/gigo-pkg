<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOrder extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'job_orders';
	public $timestamps = true;
	protected $fillable = [
		"company_id",
		"number",
		"vehicle_id",
		"km_reading",
		"hr_reading",
		"km_reading_type_id",
		"type_id",
		"quote_type_id",
		"service_type_id",
		"outlet_id",
		"driver_name",
		"driver_mobile_number",
		"contact_number",
		"driver_license_expiry_date",
		"insurance_expiry_date",
		"voc",
		"is_road_test_required",
		"road_test_done_by_id",
		"road_test_performed_by_id",
		"road_test_report",
		"expert_diagnosis_report",
		"expert_diagnosis_report_by_id ",
		"warranty_expiry_date",
		"ewp_expiry_date",
		"status_id",
		"estimated_delivery_date",
		"estimation_type_id",
		"minimum_payable_amount",
		"service_advisor_id",
		"floor_supervisor_id",
	];

	protected $dates = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	protected $casts = [
		'is_sold' => 'boolean',
	];

	protected $appends = [
		'est_delivery_date',
		'est_delivery_time',
	];

	// Getters --------------------------------------------------------------

	//APPEND - INBETWEEN REGISTRATION NUMBER
	public function getRegistrationNumberAttribute($value) {
		$registration_number = '';

		if ($value) {
			$value = str_replace('-', '', $value);
			$reg_number = str_split($value);

			$last_four_numbers = substr($value, -4);

			$registration_number .= $reg_number[0] . $reg_number[1] . '-' . $reg_number[2] . $reg_number[3] . '-';

			if (is_numeric($reg_number[4])) {
				$registration_number .= $last_four_numbers;
			} else {
				$registration_number .= $reg_number[4];
				if (is_numeric($reg_number[5])) {
					$registration_number .= '-' . $last_four_numbers;
				} else {
					$registration_number .= $reg_number[5] . '-' . $last_four_numbers;
				}
			}
		}
		return $this->attributes['registration_number'] = $registration_number;
	}

	public function getDriverLicenseExpiryDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getInsuranceExpiryDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getWarrantyExpiryDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getEwpExpiryDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getEstimatedDeliveryDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y h:i A ', strtotime($date));
	}

	public function getEstimatedAmountAttribute($value) {
		return empty($value) ? '' : round($value);
	}

	public function getAmcStartingDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getAmcEndingDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getEstDeliveryDateAttribute() {
		return empty($this->attributes['estimated_delivery_date']) ? date('d-m-Y') : date('d-m-Y', strtotime($this->attributes['estimated_delivery_date']));
	}

	public function getEstDeliveryTimeAttribute() {
		return empty($this->attributes['estimated_delivery_date']) ? date('h:i A') : date('h:i A', strtotime($this->attributes['estimated_delivery_date']));
	}

	// Setters --------------------------------------------------------------

	public function setDriverLicenseExpiryDateAttribute($date) {
		return $this->attributes['driver_license_expiry_date'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function setInsuranceExpiryDateAttribute($date) {
		return $this->attributes['insurance_expiry_date'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function setWarrantyExpiryDateAttribute($date) {
		return $this->attributes['warranty_expiry_date'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function setEwpExpiryDateAttribute($date) {
		return $this->attributes['ewp_expiry_date'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function getCallDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	// Relationships --------------------------------------------------------------

	public static function relationships($action = '') {
		$relationships = [
			'type',
			'outlet',
			'vehicle',
			'vehicle.model',
			'vehicle.bharat_stage',
			'customer',
			'jobCard',
			'serviceType',
			'status',
		];
		return $relationships;
	}

	public function customer() {
		return $this->belongsTo('App\Customer');
	}

	public function customerVoices() {
		return $this->belongsToMany('App\CustomerVoice', 'job_order_customer_voice', 'job_order_id', 'customer_voice_id')->withPivot(['details']);
	}

	public function vehicleInspectionItems() {
		return $this->belongsToMany('App\VehicleInspectionItem', 'job_order_vehicle_inspection_item', 'job_order_id', 'vehicle_inspection_item_id')->withPivot(['status_id']);
	}

	public function vehicleInventoryItem() {
		return $this->belongsToMany('App\VehicleInventoryItem', 'job_order_vehicle_inventory_item', 'job_order_id', 'vehicle_inventory_item_id')->withPivot(['is_available', 'remarks']);
	}

	public function inwardProcessChecks() {
		return $this->belongsToMany('App\Config', 'inward_process_check', 'job_order_id', 'tab_id')->withPivot(['is_form_filled']);
	}

	public function jobOrderRepairOrders() {
		return $this->hasMany('App\JobOrderRepairOrder', 'job_order_id');
	}

	public function jobOrderParts() {
		return $this->hasMany('App\JobOrderPart', 'job_order_id');
	}

	public function gateLog() {
		return $this->hasOne('App\GateLog', 'job_order_id');
	}

	public function vehicle() {
		return $this->belongsTo('App\Vehicle', 'vehicle_id');
	}

	public function tradePlate() {
		return $this->belongsTo('App\TradePlateNumber', 'trade_plate_number_id');
	}

	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}

	public function type() {
		return $this->belongsTo('Abs\GigoPkg\ServiceOrderType', 'type_id');
	}

	public function estimationType() {
		return $this->belongsTo('Abs\GigoPkg\EstimationType', 'estimation_type_id');
	}

	public function outlet() {
		return $this->belongsTo('App\Outlet', 'outlet_id');
	}

	public function campaigns() {
		return $this->hasMany('Abs\GigoPkg\JobOrderCampaign', 'job_order_id', 'id');
	}

	//issue : company condition not required
	// public function serviceOrederType() {
	// 	return $this->belongsTo('App\ServiceOrderType', 'type_id')->where('company_id', Auth::user()->company_id);
	// }

	public function quoteType() {
		return $this->belongsTo('App\QuoteType', 'quote_type_id')
		// ->where('company_id', Auth::user()->company_id)
		;
	}

	public function serviceType() {
		return $this->belongsTo('Abs\GigoPkg\ServiceType', 'service_type_id')
		// ->where('company_id', Auth::user()->company_id)
		;
	}

	public function kmReadingType() {
		return $this->belongsTo('App\Config', 'km_reading_type_id');
	}

	public function roadTestDoneBy() {
		return $this->belongsTo('App\Config', 'road_test_done_by_id');
	}

	public function roadTestPreferedBy() {
		return $this->belongsTo('App\User', 'road_test_performed_by_id')
		// ->where('company_id', Auth::user()->company_id)
		;
	}

	public function expertDiagnosisReportBy() {
		return $this->belongsTo('App\User', 'expert_diagnosis_report_by_id')
		// ->where('company_id', Auth::user()->company_id)
		;
	}

	public function floorAdviser() {
		return $this->belongsTo('App\User', 'floor_supervisor_id')
		// ->where('company_id', Auth::user()->company_id)
		;
	}

	public function CREUser() {
		return $this->belongsTo('App\User', 'cre_user_id');
	}

	public function jobCard() {
		return $this->hasOne('App\JobCard', 'job_order_id');
	}

	public function warrentyPolicyAttachment() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 256);
	}

	public function EWPAttachment() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 257);
	}

	public function AMCAttachment() {
		return $this->hasMany('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 258);
	}

	public function driverLicenseAttachment() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 251);
	}

	public function insuranceAttachment() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 252);
	}

	public function rcBookAttachment() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 250);
	}

	public function customerApprovalAttachment() {
		return $this->hasMany('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 254);
	}

	public function customerESign() {
		return $this->hasMany('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 253);
	}

	public function serviceAdviser() {
		return $this->belongsTo('App\User', 'service_advisor_id')
		// ->where('company_id', Auth::user()->company_id)
		;
	}

	public function VOCAttachment() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 10090);
	}

	// Query Scopes --------------------------------------------------------------

	public function scopeFilterSearch($query, $term) {
		if (strlen($term)) {
			$query->where(function ($query) use ($term) {
				$query->orWhere('number', 'LIKE', '%' . $term . '%');
				$query->orWhere('driver_name', 'LIKE', '%' . $term . '%');
				$query->orWhere('contact_number', 'LIKE', '%' . $term . '%');
			});
		}
	}

	public function scopeFilterTypeIn($query, $typeIds) {
		$query->whereIn('type_id', $typeIds);
	}

	// Operations --------------------------------------------------------------

	// Static Operations --------------------------------------------------------------

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
