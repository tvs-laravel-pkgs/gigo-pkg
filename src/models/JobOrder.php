<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\JobCard;
use Abs\GigoPkg\JobOrder;
use Abs\HelperPkg\Traits\SeederTrait;
use Abs\TaxPkg\Tax;
use App\BaseModel;
use App\Company;
use App\Config;
use App\JobOrderEstimate;
use App\SplitOrderType;
use Auth;
use DB;
use File;
use Illuminate\Database\Eloquent\SoftDeletes;
use PDF;
use Storage;

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

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
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

	public function getEstimationApprovedAtAttribute($date) {
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
		return $this->belongsToMany('App\VehicleInventoryItem', 'job_order_vehicle_inventory_item', 'job_order_id', 'vehicle_inventory_item_id')->withPivot(['is_available', 'remarks', 'gate_log_id', 'entry_type_id']);
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
		return $this->hasOne('App\GateLog', 'job_order_id')->orderBy('id', 'DESC')->limit(1);
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

	public function tradePlateNumber() {
		return $this->belongsTo('App\TradePlateNumber', 'road_test_trade_plate_number_id');
	}

	public function GateInTradePlateNumber() {
		return $this->belongsTo('App\TradePlateNumber', 'gatein_trade_plate_number_id');
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

	public function frontSideAttachment() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 10091);
	}

	public function backSideAttachment() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 10092);
	}

	public function leftSideAttachment() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 10093);
	}

	public function rightSideAttachment() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 10094);
	}

	public function otherVehicleAttachment() {
		return $this->hasMany('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 10095);
	}

	public function estimationDeniedPaymentDetails() {
		return $this->hasMany('App\Invoice', 'entity_id', 'id')->where('invoice_of_id', 7427);
	}
	public function gatePass() {
		return $this->hasOne('App\GatePass', 'job_order_id');
	}

	public function gigoInvoices() {
		return $this->hasOne('Abs\GigoPkg\GigoInvoice', 'entity_id', 'id')->where('invoice_of_id', 7427);
	}

	public function amcMember() {
		return $this->belongsTo('App\AmcMember', 'service_policy_id');
	}

	public function gateInDriverSign() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 10098);
	}

	public function gateInSecuritySign() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 10099);
	}

	public function gateOutDriverSign() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 10100);
	}

	public function gateOutSecuritySign() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 227)->where('attachment_type_id', 10101);
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

	public static function generateInventoryPDF($job_order_id, $type) {
		$job_order = JobOrder::with([
			'vehicle',
			'vehicle.model',
			'outlet',
			'gateLog',
			'gatePass',
			'vehicle.currentOwner.customer',
			'vehicle.currentOwner.customer.primaryAddress',
			'vehicle.currentOwner.customer.primaryAddress.country',
			'vehicle.currentOwner.customer.primaryAddress.state',
			'vehicle.currentOwner.customer.primaryAddress.city',
			'serviceAdviser',
			'customerESign',
			'gateInDriverSign',
			'gateInSecuritySign',
			'gateOutDriverSign',
			'gateOutSecuritySign',
		])

			->find($job_order_id);

		$params['field_type_id'] = [11, 12];
		$company_id = $job_order->company_id;

		// $data['extras'] = [
		// 	'inventory_type_list' => VehicleInventoryItem::getInventoryList($job_order_id, $params, '', '', $company_id),
		// ];

		$vehicle_inventories = [];

		$inventory_list = VehicleInventoryItem::where('company_id', $company_id)->whereIn('field_type_id', [11, 12])->orderBy('id')->get();

		if ($inventory_list) {
			foreach ($inventory_list as $key => $inventory) {
				$vehicle_inventories[$key]['id'] = $inventory['id'];
				$vehicle_inventories[$key]['name'] = $inventory['name'];

				//Check GateIn
				$gate_in_inventory = DB::table('job_order_vehicle_inventory_item')->where('job_order_id', $job_order_id)->where('gate_log_id', $job_order->gateLog->id)->where('vehicle_inventory_item_id', $inventory['id'])->where('entry_type_id', 11300)->first();
				if ($gate_in_inventory) {
					$vehicle_inventories[$key]['gate_in_checked'] = true;
					$vehicle_inventories[$key]['gate_in_remarks'] = $gate_in_inventory->remarks;
				} else {
					$vehicle_inventories[$key]['gate_in_checked'] = false;
					$vehicle_inventories[$key]['gate_in_remarks'] = '';
				}

				//Check GateOut
				$gate_out_inventory = DB::table('job_order_vehicle_inventory_item')->where('job_order_id', $job_order_id)->where('gate_log_id', $job_order->gateLog->id)->where('vehicle_inventory_item_id', $inventory['id'])->where('entry_type_id', 11301)->first();
				if ($gate_out_inventory) {
					$vehicle_inventories[$key]['gate_out_checked'] = true;
					$vehicle_inventories[$key]['gate_out_remarks'] = $gate_out_inventory->remarks;
				} else {
					$vehicle_inventories[$key]['gate_out_checked'] = false;
					$vehicle_inventories[$key]['gate_out_remarks'] = '';
				}
			}
		}

		$data['type'] = $type;

		$save_path = storage_path('app/public/gigo/pdf');
		Storage::makeDirectory($save_path, 0777);

		if (!Storage::disk('public')->has('gigo/pdf/')) {
			Storage::disk('public')->makeDirectory('gigo/pdf/');
		}

		$data['date'] = date('d-m-Y h:i A');

		if (count($job_order->customerESign) > 0) {
			$job_order->esign_img = 'app/public/gigo/job_order/' . $job_order->customerESign[0]->name;
		} else {
			$job_order->esign_img = '';
		}

		//Gate In Security Sign
		if ($job_order->gateInSecuritySign) {
			$job_order->gate_in_security_signature = 'app/public/gigo/job_order/attachments/' . $job_order->gateInSecuritySign->name;
		} else {
			$job_order->gate_in_security_signature = '';
		}

		//Gate In Driver Sign
		if ($job_order->gateInDriverSign) {
			$job_order->gate_in_driver_signature = 'app/public/gigo/job_order/attachments/' . $job_order->gateInDriverSign->name;
		} else {
			$job_order->gate_in_driver_signature = '';
		}

		//Gate Out Security Sign
		if ($job_order->gateOutDriverSign) {
			$job_order->gate_out_driver_signature = 'app/public/gigo/job_order/attachments/' . $job_order->gateOutDriverSign->name;
		} else {
			$job_order->gate_out_driver_signature = '';
		}

		//Gate Out Driver Sign
		if ($job_order->gateOutSecuritySign) {
			$job_order->gate_out_security_signature = 'app/public/gigo/job_order/attachments/' . $job_order->gateOutSecuritySign->name;
		} else {
			$job_order->gate_out_security_signature = '';
		}

		$data['gate_pass'] = $job_order;
		$data['vehicle_inventories'] = $vehicle_inventories;

		$name = $job_order_id . '_inward_inventory.pdf';

		$pdf = PDF::loadView('pdf-gigo/inward-inventory', $data)->setPaper('a4', 'portrait');

		$img_path = $save_path . '/' . $name;
		if (File::exists($img_path)) {
			File::delete($img_path);
		}

		$pdf->save(storage_path('app/public/gigo/pdf/' . $name));

		return true;
	}

	public static function generateInspectionPDF($job_order_id) {
		$job_order = JobOrder::with([
			'vehicle',
			'vehicle.model',
			'vehicle.status',
			'outlet',
			'gateLog',
			'vehicle.currentOwner.customer',
			'vehicle.currentOwner.customer.primaryAddress',
			'vehicle.currentOwner.customer.primaryAddress.country',
			'vehicle.currentOwner.customer.primaryAddress.state',
			'vehicle.currentOwner.customer.primaryAddress.city',
			'vehicleInspectionItems',
			'floorAdviser',
			'serviceAdviser',
			'customerESign',
		])
			->select([
				'job_orders.*',
				DB::raw('DATE_FORMAT(job_orders.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
			])
			->find($job_order_id);

		$company_id = $job_order->company_id;

		if (!Storage::disk('public')->has('gigo/pdf/')) {
			Storage::disk('public')->makeDirectory('gigo/pdf/');
		}

		$data['date'] = date('d-m-Y h:i A');

		$save_path = storage_path('app/public/gigo/pdf');
		Storage::makeDirectory($save_path, 0777);

		$vehicle_inspection_item_groups = array();
		if (count($job_order->vehicleInspectionItems) > 0) {
			$vehicle_inspection_item_group = VehicleInspectionItemGroup::where('company_id', Auth::user()->company_id)->select('id', 'name')->get();

			foreach ($vehicle_inspection_item_group as $key => $value) {
				$item_group = array();
				$item_group['id'] = $value->id;
				$item_group['name'] = $value->name;

				$inspection_items = VehicleInspectionItem::where('group_id', $value->id)->get()->keyBy('id');

				$vehicle_inspections = $job_order->vehicleInspectionItems()->orderBy('vehicle_inspection_item_id')->get()->toArray();

				if (count($vehicle_inspections) > 0) {
					foreach ($vehicle_inspections as $value) {
						if (isset($inspection_items[$value['id']])) {
							$inspection_items[$value['id']]->status_id = $value['pivot']['status_id'];
						}
					}
				}
				$item_group['vehicle_inspection_items'] = $inspection_items;

				$vehicle_inspection_item_groups[] = $item_group;
			}
		}

		$job_order->vehicle_inspection_items = $vehicle_inspection_item_groups;

		if ($job_order->customerESign && count($job_order->customerESign) > 0) {
			$job_order->esign_img = 'app/public/gigo/job_order/' . $job_order->customerESign[0]->name;
		} else {
			$job_order->esign_img = '';
		}

		$data['gate_pass'] = $job_order;

		$data['date'] = date('d-m-Y');

		$name = $job_order_id . '_inward_inspection.pdf';

		$pdf = PDF::loadView('pdf-gigo/inward-inspection', $data)->setPaper('a4', 'portrait');

		$img_path = $save_path . '/' . $name;
		if (File::exists($img_path)) {
			File::delete($img_path);
		}

		$pdf->save(storage_path('app/public/gigo/pdf/' . $name));

		return true;
	}

	public static function generateManualJoPDF($job_order_id) {

		$job_order = JobOrder::with([
			'outlet',
			'vehicle.currentOwner.customer',
			'jobOrderRepairOrders' => function ($q) {
				$q->whereNull('removal_reason_id');
			},
			'jobOrderRepairOrders.repairOrder',
			'jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrderRepairOrders.customerVoice',
			'floorAdviser',
			'serviceAdviser',
			'roadTestPreferedBy.employee',
			'jobOrderParts' => function ($q) {
				$q->whereNull('removal_reason_id');
			},
			'jobOrderParts.part',
			'jobOrderParts.part.taxCode',
			'jobOrderParts.part.taxCode.taxes',
			'jobOrderParts.customerVoice',
			'roadTestDoneBy',
			'roadTestPreferedBy',
			'roadTestPreferedBy.employee',
			'serviceAdviser',
		])
			->find($job_order_id);

		$parts_amount = 0;
		$labour_amount = 0;
		$total_amount = 0;

		if ($job_order->vehicle->currentOwner->customer->primaryAddress) {
			//Check which tax applicable for customer
			if ($job_order->outlet->state_id == $job_order->vehicle->currentOwner->customer->primaryAddress->state_id) {
				$tax_type = 1160; //Within State
			} else {
				$tax_type = 1161; //Inter State
			}
		} else {
			$tax_type = 1160; //Within State
		}

		$customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		//Count Tax Type
		$taxes = Tax::get();

		$tax_percentage = 0;
		$labour_details = array();

		$i = 1;
		if ($job_order->jobOrderRepairOrders) {
			foreach ($job_order->jobOrderRepairOrders as $key => $labour) {
				$total_amount = 0;
				$labour_details[$key]['sno'] = $i;
				$labour_details[$key]['name'] = $labour->repairOrder->code . ' / ' . $labour->repairOrder->name;
				$labour_details[$key]['split_order_type'] = $labour->splitOrderType ? $labour->splitOrderType->code . " / " . $labour->splitOrderType->name : '-';
				$labour_details[$key]['voc'] = $labour->customerVoice ? $labour->customerVoice->name . ' / ' . $labour->customerVoice->name : '-';

				$tax_amount = 0;
				$tax_values = array();
				if ($labour->repairOrder->taxCode) {
					foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
						$percentage_value = 0;
						if ($value->type_id == $tax_type) {
							$percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
							$percentage_value = number_format((float) $percentage_value, 2, '.', '');
						}
						$tax_values[$tax_key] = $percentage_value;
						$tax_amount += $percentage_value;
					}
				}

				$total_amount = $tax_amount + $labour->amount;
				$total_amount = number_format((float) $total_amount, 2, '.', '');
				$labour_amount += $total_amount;
				$labour_details[$key]['total_amount'] = number_format($total_amount, 2);
				$i++;
			}
		}

		$part_details = array();
		if ($job_order->jobOrderParts) {
			foreach ($job_order->jobOrderParts as $key => $parts) {
				$total_amount = 0;
				$part_details[$key]['sno'] = $i;
				$part_details[$key]['name'] = $parts->part->code . ' / ' . $parts->part->name;

				$part_details[$key]['split_order_type'] = $parts->splitOrderType ? $parts->splitOrderType->code . " / " . $parts->splitOrderType->name : '-';
				$part_details[$key]['voc'] = $parts->customerVoice ? $parts->customerVoice->name . ' / ' . $parts->customerVoice->name : '-';

				$tax_amount = 0;
				$tax_values = array();
				if ($parts->part->taxCode) {
					if (count($parts->part->taxCode->taxes) > 0) {
						foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								$percentage_value = ($parts->amount * $value->pivot->percentage) / 100;
								$percentage_value = number_format((float) $percentage_value, 2, '.', '');
							}
							$tax_values[$tax_key] = $percentage_value;
							$tax_amount += $percentage_value;
						}
					}
				}

				$total_amount = $tax_amount + $parts->amount;
				$total_amount = number_format((float) $total_amount, 2, '.', '');
				$parts_amount += $total_amount;
				$part_details[$key]['total_amount'] = number_format($total_amount, 2);
				$i++;
			}
		}

		$total_amount = $parts_amount + $labour_amount;
		$total_amount = round($total_amount);

		$data['part_details'] = $part_details;
		$data['labour_details'] = $labour_details;

		$save_path = storage_path('app/public/gigo/pdf');
		Storage::makeDirectory($save_path, 0777);

		if (!Storage::disk('public')->has('gigo/pdf/')) {
			Storage::disk('public')->makeDirectory('gigo/pdf/');
		}

		$data['title'] = 'Manual Job Order';
		$data['job_order'] = $job_order;
		$data['total_amount'] = number_format($total_amount, 2);

		$name = $job_order->id . '_manual_job_order.pdf';

		$pdf = PDF::loadView('pdf-gigo/manual-joborder', $data)->setPaper('a4', 'landscape');

		$img_path = $save_path . '/' . $name;
		if (File::exists($img_path)) {
			File::delete($img_path);
		}

		$pdf->save(storage_path('app/public/gigo/pdf/' . $name));

		return true;
	}

	public static function generateEstimateGatePassPDF($job_order_id, $type) {
		$data['gate_pass'] = $job_order = JobOrder::with([
			'type',
			'quoteType',
			'serviceType',
			'vehicle',
			'vehicle.model',
			'vehicle.status',
			'outlet',
			'gateLog',
			'gatePass',
			'vehicle.currentOwner.customer',
			'vehicle.currentOwner.customer.primaryAddress',
			'vehicle.currentOwner.customer.primaryAddress.country',
			'vehicle.currentOwner.customer.primaryAddress.state',
			'vehicle.currentOwner.customer.primaryAddress.city',
			'jobOrderRepairOrders.repairOrder',
			'jobOrderRepairOrders.repairOrder.repairOrderType',
			'floorAdviser',
			'serviceAdviser',
		])
			->select([
				'job_orders.*',
				DB::raw('DATE_FORMAT(job_orders.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
			])
			->find($job_order_id);

		$params['field_type_id'] = [11, 12];
		$company_id = $job_order->company_id;
		// $data['extras'] = [
		// 	'inventory_type_list' => VehicleInventoryItem::getInventoryList($job_order_id, $params, '', '', $company_id),
		// ];

		$vehicle_inventories = [];

		$inventory_list = VehicleInventoryItem::where('company_id', $company_id)->whereIn('field_type_id', [11, 12])->orderBy('id')->get();

		if ($inventory_list) {
			foreach ($inventory_list as $key => $inventory) {
				$vehicle_inventories[$key]['id'] = $inventory['id'];
				$vehicle_inventories[$key]['name'] = $inventory['name'];

				//Check GateIn
				$gate_in_inventory = DB::table('job_order_vehicle_inventory_item')->where('job_order_id', $job_order_id)->where('gate_log_id', $job_order->gateLog->id)->where('vehicle_inventory_item_id', $inventory['id'])->where('entry_type_id', 11300)->first();
				if ($gate_in_inventory) {
					$vehicle_inventories[$key]['gate_in_checked'] = true;
					$vehicle_inventories[$key]['gate_in_remarks'] = $gate_in_inventory->remarks;
				} else {
					$vehicle_inventories[$key]['gate_in_checked'] = false;
					$vehicle_inventories[$key]['gate_in_remarks'] = '';
				}

				//Check GateOut
				$gate_out_inventory = DB::table('job_order_vehicle_inventory_item')->where('job_order_id', $job_order_id)->where('gate_log_id', $job_order->gateLog->id)->where('vehicle_inventory_item_id', $inventory['id'])->where('entry_type_id', 11301)->first();
				if ($gate_out_inventory) {
					$vehicle_inventories[$key]['gate_out_checked'] = true;
					$vehicle_inventories[$key]['gate_out_remarks'] = $gate_out_inventory->remarks;
				} else {
					$vehicle_inventories[$key]['gate_out_checked'] = false;
					$vehicle_inventories[$key]['gate_out_remarks'] = '';
				}
			}
		}

		$data['type'] = $type;
		$data['vehicle_inventories'] = $vehicle_inventories;

		$save_path = storage_path('app/public/gigo/pdf');
		Storage::makeDirectory($save_path, 0777);

		if (!Storage::disk('public')->has('gigo/pdf/')) {
			Storage::disk('public')->makeDirectory('gigo/pdf/');
		}

		$data['date'] = date('d-m-Y');

		$name = $job_order_id . '_gatepass.pdf';

		$pdf = PDF::loadView('pdf-gigo/estimation-gate-pass', $data)->setPaper('a4', 'portrait');

		$img_path = $save_path . '/' . $name;
		if (File::exists($img_path)) {
			File::delete($img_path);
		}

		$pdf->save(storage_path('app/public/gigo/pdf/' . $name));

		return true;
	}

	public static function generateEstimatePDF($job_order_id) {

		$estimate_order = JobOrderEstimate::select('job_order_estimates.id', 'job_order_estimates.created_at')->where('job_order_estimates.job_order_id', $job_order_id)->orderBy('job_order_estimates.id', 'ASC')->first();

		if ($estimate_order) {
			$data['estimate'] = $job_order = JobOrder::with([
				'type',
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'outlet',
				'gateLog',
				'vehicle.currentOwner.customer',
				'vehicle.currentOwner.customer.primaryAddress',
				'vehicle.currentOwner.customer.primaryAddress.country',
				'vehicle.currentOwner.customer.primaryAddress.state',
				'vehicle.currentOwner.customer.primaryAddress.city',
				'serviceType',
				'jobOrderRepairOrders' => function ($q) use ($estimate_order) {
					$q->where('estimate_order_id', $estimate_order->id)->whereNull('removal_reason_id');
				},
				'jobOrderRepairOrders.repairOrder',
				'jobOrderRepairOrders.repairOrder.repairOrderType',
				'floorAdviser',
				'serviceAdviser',
				'roadTestPreferedBy.employee',
				'jobOrderParts' => function ($q) use ($estimate_order) {
					$q->where('estimate_order_id', $estimate_order->id)->whereNull('removal_reason_id');
				},
				'jobOrderParts.part',
				'jobOrderParts.part.taxCode',
				'jobOrderParts.part.taxCode.taxes'])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d-%m-%Y") as jobdate'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->find($job_order_id);

			$parts_amount = 0;
			$labour_amount = 0;
			$total_amount = 0;

			if ($job_order->vehicle->currentOwner->customer->primaryAddress) {
				//Check which tax applicable for customer
				if ($job_order->outlet->state_id == $job_order->vehicle->currentOwner->customer->primaryAddress->state_id) {
					$tax_type = 1160; //Within State
				} else {
					$tax_type = 1161; //Inter State
				}
			} else {
				$tax_type = 1160; //Within State
			}

			$customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

			//Count Tax Type
			$taxes = Tax::get();

			//GET SEPERATE TAXEX
			$seperate_tax = array();
			for ($i = 0; $i < count($taxes); $i++) {
				$seperate_tax[$i] = 0.00;
			}

			$tax_percentage = 0;
			$labour_details = array();
			if ($job_order->jobOrderRepairOrders) {
				$i = 1;
				$total_labour_qty = 0;
				$total_labour_mrp = 0;
				$total_labour_price = 0;
				$total_labour_tax = 0;
				foreach ($job_order->jobOrderRepairOrders as $key => $labour) {
					if (in_array($labour->split_order_type_id, $customer_paid_type_id) || !$labour->split_order_type_id) {
						if ($labour->is_free_service != 1 && $labour->removal_reason_id == null) {
							$total_amount = 0;
							$labour_details[$key]['sno'] = $i;
							$labour_details[$key]['code'] = $labour->repairOrder->code;
							$labour_details[$key]['name'] = $labour->repairOrder->name;
							$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
							$labour_details[$key]['qty'] = $labour->qty;
							$labour_details[$key]['amount'] = $labour->amount;
							$labour_details[$key]['rate'] = $labour->repairOrder->amount;
							$labour_details[$key]['is_free_service'] = $labour->is_free_service;
							$tax_amount = 0;
							$tax_percentage = 0;
							$labour_total_cgst = 0;
							$labour_total_sgst = 0;
							$labour_total_igst = 0;
							$tax_values = array();
							if ($labour->repairOrder->taxCode) {
								foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
									$percentage_value = 0;
									if ($value->type_id == $tax_type) {
										$tax_percentage += $value->pivot->percentage;
										$percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
										$percentage_value = number_format((float) $percentage_value, 2, '.', '');
									}
									$tax_values[$tax_key] = $percentage_value;
									$tax_amount += $percentage_value;

									if (count($seperate_tax) > 0) {
										$seperate_tax_value = $seperate_tax[$tax_key];
									} else {
										$seperate_tax_value = 0;
									}
									$seperate_tax[$tax_key] = $seperate_tax_value + $percentage_value;
								}
							} else {
								for ($i = 0; $i < count($taxes); $i++) {
									$tax_values[$i] = 0.00;
								}
							}
							$labour_total_sgst += $labour_total_sgst;
							$labour_total_igst += $labour_total_igst;
							$total_labour_qty += $labour->qty;
							$total_labour_mrp += $labour->amount;
							$total_labour_price += $labour->repairOrder->amount;
							$total_labour_tax += $tax_amount;

							$labour_details[$key]['tax_values'] = $tax_values;
							$labour_details[$key]['tax_amount'] = $tax_amount;
							$total_amount = $tax_amount + $labour->amount;
							$total_amount = number_format((float) $total_amount, 2, '.', '');

							$labour_details[$key]['total_amount'] = $total_amount;
							// if ($labour->is_free_service != 1) {
							$labour_amount += $total_amount;
							// }
							$i++;
						}
					}
				}
			}

			$part_details = array();
			if ($job_order->jobOrderParts) {
				$j = 1;
				$total_parts_qty = 0;
				$total_parts_mrp = 0;
				$total_parts_price = 0;
				$total_parts_tax = 0;
				foreach ($job_order->jobOrderParts as $key => $parts) {
					if (in_array($parts->split_order_type_id, $customer_paid_type_id) || !$parts->split_order_type_id) {
						if ($parts->is_free_service != 1 && $parts->removal_reason_id == null) {
							$total_amount = 0;
							$part_details[$key]['sno'] = $j;
							$part_details[$key]['code'] = $parts->part->code;
							$part_details[$key]['name'] = $parts->part->name;
							$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
							$part_details[$key]['qty'] = $parts->qty;
							$part_details[$key]['rate'] = $parts->rate;
							$part_details[$key]['amount'] = $parts->amount;
							$part_details[$key]['is_free_service'] = $parts->is_free_service;
							$tax_amount = 0;
							$tax_percentage = 0;
							$tax_values = array();
							if ($parts->part->taxCode) {
								foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
									$percentage_value = 0;
									if ($value->type_id == $tax_type) {
										$tax_percentage += $value->pivot->percentage;
										$percentage_value = ($parts->amount * $value->pivot->percentage) / 100;
										$percentage_value = number_format((float) $percentage_value, 2, '.', '');
									}
									$tax_values[$tax_key] = $percentage_value;
									$tax_amount += $percentage_value;

									if (count($seperate_tax) > 0) {
										$seperate_tax_value = $seperate_tax[$tax_key];
									} else {
										$seperate_tax_value = 0;
									}
									$seperate_tax[$tax_key] = $seperate_tax_value + $percentage_value;
								}
							} else {
								for ($i = 0; $i < count($taxes); $i++) {
									$tax_values[$i] = 0.00;
								}
							}

							$total_parts_qty += $parts->qty;
							$total_parts_mrp += $parts->rate;
							$total_parts_price += $parts->amount;
							$total_parts_tax += $tax_amount;

							$part_details[$key]['tax_values'] = $tax_values;
							$part_details[$key]['tax_amount'] = $tax_amount;
							$total_amount = $tax_amount + $parts->amount;
							$total_amount = number_format((float) $total_amount, 2, '.', '');
							if ($parts->is_free_service != 1) {
								$parts_amount += $total_amount;
							}
							$part_details[$key]['total_amount'] = $total_amount;
							$j++;
						}
					}
				}
			}

			foreach ($seperate_tax as $key => $s_tax) {
				$seperate_tax[$key] = convert_number_to_words($s_tax);
			}
			$data['seperate_taxes'] = $seperate_tax;

			$total_taxable_amount = $total_labour_tax + $total_parts_tax;
			$data['tax_percentage'] = convert_number_to_words($tax_percentage);
			$data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

			$total_amount = $parts_amount + $labour_amount;
			$data['taxes'] = $taxes;
			$data['estimate_date'] = $estimate_order->created_at;
			$data['part_details'] = $part_details;
			$data['labour_details'] = $labour_details;
			$data['total_labour_qty'] = $total_labour_qty;
			$data['total_labour_mrp'] = $total_labour_mrp;
			$data['total_labour_price'] = $total_labour_price;
			$data['total_labour_tax'] = $total_labour_tax;

			$data['total_parts_qty'] = $total_parts_qty;
			$data['total_parts_mrp'] = $total_parts_mrp;
			$data['total_parts_price'] = $total_parts_price;
			$data['total_parts_tax'] = $total_parts_tax;
			$data['parts_total_amount'] = number_format($parts_amount, 2);
			$data['labour_total_amount'] = number_format($labour_amount, 2);
			//FOR ROUND OFF
			if ($total_amount <= round($total_amount)) {
				$round_off = round($total_amount) - $total_amount;
			} else {
				$round_off = round($total_amount) - $total_amount;
			}
			// dd(number_format($round_off));
			$data['round_total_amount'] = number_format($round_off, 2);
			$data['total_amount'] = number_format(round($total_amount), 2);

			$data['title'] = 'Estimate';

			$save_path = storage_path('app/public/gigo/pdf');
			Storage::makeDirectory($save_path, 0777);

			if (!Storage::disk('public')->has('gigo/pdf/')) {
				Storage::disk('public')->makeDirectory('gigo/pdf/');
			}

			$name = $job_order->id . '_estimate.pdf';

			$pdf = PDF::loadView('pdf-gigo/estimate-pdf', $data)->setPaper('a4', 'portrait');

			$img_path = $save_path . '/' . $name;
			if (File::exists($img_path)) {
				File::delete($img_path);
			}

			$pdf->save(storage_path('app/public/gigo/pdf/' . $name));
		}
		return true;
	}

	public static function generateRevisedEstimatePDF($job_order_id) {

		$estimate_order = JobOrderEstimate::select('job_order_estimates.id', 'job_order_estimates.created_at')->where('job_order_estimates.job_order_id', $job_order_id)->orderBy('job_order_estimates.id', 'ASC')->first();

		$data['estimate'] = $job_order = JobOrder::with([
			'type',
			'vehicle',
			'vehicle.model',
			'vehicle.status',
			'outlet',
			'gateLog',
			'vehicle.currentOwner.customer',
			'vehicle.currentOwner.customer.primaryAddress',
			'vehicle.currentOwner.customer.primaryAddress.country',
			'vehicle.currentOwner.customer.primaryAddress.state',
			'vehicle.currentOwner.customer.primaryAddress.city',
			'serviceType',
			'jobOrderRepairOrders' => function ($q) {
				$q->whereNull('removal_reason_id');
			},
			'jobOrderRepairOrders.repairOrder',
			'jobOrderRepairOrders.repairOrder.repairOrderType',
			'floorAdviser',
			'serviceAdviser',
			'roadTestPreferedBy.employee',
			'jobOrderParts' => function ($q) {
				$q->whereNull('removal_reason_id');
			},
			'jobOrderParts.part',
			'jobOrderParts.part.taxCode',
			'jobOrderParts.part.taxCode.taxes'])
			->select([
				'job_orders.*',
				DB::raw('DATE_FORMAT(job_orders.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
			])
			->find($job_order_id);
		$parts_amount = 0;
		$labour_amount = 0;
		$total_amount = 0;

		if ($job_order->vehicle->currentOwner->customer->primaryAddress) {
			//Check which tax applicable for customer
			if ($job_order->outlet->state_id == $job_order->vehicle->currentOwner->customer->primaryAddress->state_id) {
				$tax_type = 1160; //Within State
			} else {
				$tax_type = 1161; //Inter State
			}
		} else {
			$tax_type = 1160; //Within State
		}

		$customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		//Count Tax Type
		$taxes = Tax::get();

		//GET SEPERATE TAXEX
		$seperate_tax = array();
		for ($i = 0; $i < count($taxes); $i++) {
			$seperate_tax[$i] = 0.00;
		}

		$tax_percentage = 0;
		$labour_details = array();
		if ($job_order->jobOrderRepairOrders) {
			$i = 1;
			$total_labour_qty = 0;
			$total_labour_mrp = 0;
			$total_labour_price = 0;
			$total_labour_tax = 0;
			foreach ($job_order->jobOrderRepairOrders as $key => $labour) {
				if (in_array($labour->split_order_type_id, $customer_paid_type_id) || !$labour->split_order_type_id) {
					if ($labour->is_free_service != 1 && $labour->removal_reason_id == null) {
						$total_amount = 0;
						$labour_details[$key]['sno'] = $i;
						$labour_details[$key]['code'] = $labour->repairOrder->code;
						$labour_details[$key]['name'] = $labour->repairOrder->name;
						$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
						$labour_details[$key]['qty'] = $labour->qty;
						$labour_details[$key]['amount'] = $labour->amount;
						$labour_details[$key]['rate'] = $labour->repairOrder->amount;
						$labour_details[$key]['is_free_service'] = $labour->is_free_service;
						$tax_amount = 0;
						$tax_percentage = 0;
						$labour_total_cgst = 0;
						$labour_total_sgst = 0;
						$labour_total_igst = 0;
						$tax_values = array();
						if ($labour->repairOrder->taxCode) {
							foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
								$percentage_value = 0;
								if ($value->type_id == $tax_type) {
									$tax_percentage += $value->pivot->percentage;
									$percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
									$percentage_value = number_format((float) $percentage_value, 2, '.', '');
								}
								$tax_values[$tax_key] = $percentage_value;
								$tax_amount += $percentage_value;

								if (count($seperate_tax) > 0) {
									$seperate_tax_value = $seperate_tax[$tax_key];
								} else {
									$seperate_tax_value = 0;
								}
								$seperate_tax[$tax_key] = $seperate_tax_value + $percentage_value;
							}
						} else {
							for ($i = 0; $i < count($taxes); $i++) {
								$tax_values[$i] = 0.00;
							}
						}
						$labour_total_sgst += $labour_total_sgst;
						$labour_total_igst += $labour_total_igst;
						$total_labour_qty += $labour->qty;
						$total_labour_mrp += $labour->amount;
						$total_labour_price += $labour->repairOrder->amount;
						$total_labour_tax += $tax_amount;

						$labour_details[$key]['tax_values'] = $tax_values;
						$labour_details[$key]['tax_amount'] = $tax_amount;
						$total_amount = $tax_amount + $labour->amount;
						$total_amount = number_format((float) $total_amount, 2, '.', '');

						$labour_details[$key]['total_amount'] = $total_amount;
						// if ($labour->is_free_service != 1) {
						$labour_amount += $total_amount;
						// }
						$i++;
					}
				}
			}
		}

		$part_details = array();
		if ($job_order->jobOrderParts) {
			$j = 1;
			$total_parts_qty = 0;
			$total_parts_mrp = 0;
			$total_parts_price = 0;
			$total_parts_tax = 0;
			foreach ($job_order->jobOrderParts as $key => $parts) {
				if (in_array($parts->split_order_type_id, $customer_paid_type_id) || !$parts->split_order_type_id) {
					if ($parts->is_free_service != 1 && $parts->removal_reason_id == null) {
						$total_amount = 0;
						$part_details[$key]['sno'] = $j;
						$part_details[$key]['code'] = $parts->part->code;
						$part_details[$key]['name'] = $parts->part->name;
						$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
						$part_details[$key]['qty'] = $parts->qty;
						$part_details[$key]['rate'] = $parts->rate;
						$part_details[$key]['amount'] = $parts->amount;
						$part_details[$key]['is_free_service'] = $parts->is_free_service;
						$tax_amount = 0;
						$tax_percentage = 0;
						$tax_values = array();
						if ($parts->part->taxCode) {
							foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
								$percentage_value = 0;
								if ($value->type_id == $tax_type) {
									$tax_percentage += $value->pivot->percentage;
									$percentage_value = ($parts->amount * $value->pivot->percentage) / 100;
									$percentage_value = number_format((float) $percentage_value, 2, '.', '');
								}
								$tax_values[$tax_key] = $percentage_value;
								$tax_amount += $percentage_value;

								if (count($seperate_tax) > 0) {
									$seperate_tax_value = $seperate_tax[$tax_key];
								} else {
									$seperate_tax_value = 0;
								}
								$seperate_tax[$tax_key] = $seperate_tax_value + $percentage_value;
							}
						} else {
							for ($i = 0; $i < count($taxes); $i++) {
								$tax_values[$i] = 0.00;
							}
						}

						$total_parts_qty += $parts->qty;
						$total_parts_mrp += $parts->rate;
						$total_parts_price += $parts->amount;
						$total_parts_tax += $tax_amount;

						$part_details[$key]['tax_values'] = $tax_values;
						$part_details[$key]['tax_amount'] = $tax_amount;
						$total_amount = $tax_amount + $parts->amount;
						$total_amount = number_format((float) $total_amount, 2, '.', '');
						if ($parts->is_free_service != 1) {
							$parts_amount += $total_amount;
						}
						$part_details[$key]['total_amount'] = $total_amount;
						$j++;
					}
				}
			}
		}

		foreach ($seperate_tax as $key => $s_tax) {
			$seperate_tax[$key] = convert_number_to_words($s_tax);
		}
		$data['seperate_taxes'] = $seperate_tax;

		$total_taxable_amount = $total_labour_tax + $total_parts_tax;
		$data['tax_percentage'] = convert_number_to_words($tax_percentage);
		$data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

		$total_amount = $parts_amount + $labour_amount;
		$data['taxes'] = $taxes;
		$data['estimate_date'] = $estimate_order->created_at;
		$data['part_details'] = $part_details;
		$data['labour_details'] = $labour_details;
		$data['total_labour_qty'] = $total_labour_qty;
		$data['total_labour_mrp'] = $total_labour_mrp;
		$data['total_labour_price'] = $total_labour_price;
		$data['total_labour_tax'] = $total_labour_tax;

		$data['total_parts_qty'] = $total_parts_qty;
		$data['total_parts_mrp'] = $total_parts_mrp;
		$data['total_parts_price'] = $total_parts_price;
		$data['total_parts_tax'] = $total_parts_tax;
		$data['parts_total_amount'] = number_format($parts_amount, 2);
		$data['labour_total_amount'] = number_format($labour_amount, 2);
		//FOR ROUND OFF
		if ($total_amount <= round($total_amount)) {
			$round_off = round($total_amount) - $total_amount;
		} else {
			$round_off = round($total_amount) - $total_amount;
		}
		// dd(number_format($round_off));
		$data['round_total_amount'] = number_format($round_off, 2);
		$data['total_amount'] = number_format(round($total_amount), 2);

		$save_path = storage_path('app/public/gigo/pdf');
		Storage::makeDirectory($save_path, 0777);

		if (!Storage::disk('public')->has('gigo/pdf/')) {
			Storage::disk('public')->makeDirectory('gigo/pdf/');
		}

		$data['title'] = 'Revised Estimate';

		$name = $job_order->id . '_revised_estimate.pdf';

		$pdf = PDF::loadView('pdf-gigo/estimate-pdf', $data)->setPaper('a4', 'portrait');

		$img_path = $save_path . '/' . $name;
		if (File::exists($img_path)) {
			File::delete($img_path);
		}

		$pdf->save(storage_path('app/public/gigo/pdf/' . $name));

		return true;
	}

	public static function generateCoveringLetterPDF($job_order_id) {

		$data['covering_letter'] = $covering_letter = JobOrder::with([
			'gatePass',
			'gigoInvoices',
			'company',
			'type',
			'vehicle',
			'vehicle.model',
			'vehicle.status',
			'outlet',
			'gateLog',
			'vehicle.currentOwner.customer',
			'vehicle.currentOwner.customer.address',
			'vehicle.currentOwner.customer.address.country',
			'vehicle.currentOwner.customer.address.state',
			'vehicle.currentOwner.customer.address.city',
			'serviceType',
			'jobOrderRepairOrders.repairOrder',
			'jobOrderRepairOrders.repairOrder.repairOrderType',
			'serviceAdviser'])
			->select([
				'job_orders.*',
				DB::raw('DATE_FORMAT(job_orders.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
			])
			->find($job_order_id);

		$gigo_invoice = [];
		if (isset($covering_letter->gigoInvoices)) {
			$gigo_invoice[0]['bill_no'] = $covering_letter->gigoInvoices->invoice_number;
			$gigo_invoice[0]['bill_date'] = date('d-m-Y', strtotime($covering_letter->gigoInvoices->invoice_date));
			$gigo_invoice[0]['invoice_amount'] = $covering_letter->gigoInvoices->invoice_amount;

			//FOR ROUND OFF
			if ($covering_letter->gigoInvoices->invoice_amount <= round($covering_letter->gigoInvoices->invoice_amount)) {
				$round_off = round($covering_letter->gigoInvoices->invoice_amount) - $covering_letter->gigoInvoices->invoice_amount;
			} else {
				$round_off = round($gigoInvoice->invoice_amount) - $covering_letter->gigoInvoices->invoice_amount;
			}

			$gigo_invoice[0]['round_off'] = number_format($round_off, 2);
			$gigo_invoice[0]['total_amount'] = number_format(round($covering_letter->gigoInvoices->invoice_amount), 2);

		}

		$data['gigo_invoices'] = $gigo_invoice;

		$save_path = storage_path('app/public/gigo/pdf');
		Storage::makeDirectory($save_path, 0777);

		if (!Storage::disk('public')->has('gigo/pdf/')) {
			Storage::disk('public')->makeDirectory('gigo/pdf/');
		}

		$name = $covering_letter->id . '_covering_letter.pdf';

		// dd($data);

		$pdf = PDF::loadView('pdf-gigo/job-order-covering-letter-pdf', $data)->setPaper('a4', 'portrait');

		$img_path = $save_path . '/' . $name;
		if (File::exists($img_path)) {
			File::delete($img_path);
		}

		$pdf->save(storage_path('app/public/gigo/pdf/' . $name));

		return true;
	}

}
