<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Abs\SerialNumberPkg\SerialNumberGroup;
use App\Attachment;
use App\BaseModel;
use App\Customer;
use App\FinancialYear;
use App\JobCard;
use App\Outlet;
use App\Vehicle;
use App\VehicleOwner;
use App\WjorPart;
use App\WjorRepairOrder;
use Auth;
use Carbon\Carbon;
use DB;
use File;
use Illuminate\Database\Eloquent\SoftDeletes;
use PDF;
use Storage;
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
		"request_type_id",
		"split_order_type_id",
		"total_labour_amount",
		"total_part_cushioning_percentage",
		"total_part_cushioning_charge",
		"total_part_amount",
		"remarks_for_not_changing_lube",
		"claim_number",
		"failure_type_id",
		"approval_rating",
	];

	protected $dates = [
		'failure_date',
		'created_at',
		'updated_at',
		'deleted_at',
	];

	// Getters --------------------------------------------------------------

	public function getFailureDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	// Setters --------------------------------------------------------------

	public function setFailureDateAttribute($value) {
		$this->fillDateAttribute('failure_date', $value);
	}

	// Relationships --------------------------------------------------------------

	public function company() {
		return $this->belongsTo('App\Company');
	}

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
		return $this->belongsTo('App\VehiclePrimaryApplication', 'primary_segment_id');
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

	public function customer() {
		return $this->belongsTo('App\Customer', 'customer_id');
	}

	public function authorizationBy() {
		return $this->belongsTo('App\User', 'authorization_by');
	}

	public function failureType() {
		return $this->belongsTo('App\FailureType', 'failure_type_id');
	}
	public function serviceTypes() {
		return $this->belongsToMany('App\ServiceType', 'wjor_service_type', 'wjor_id', 'service_type_id');
	}

	public function wjorRepairOrders() {
		return $this->hasMany('App\WjorRepairOrder', 'wjor_id')
		// ->withPivot(['net_amount', 'tax_total', 'total_amount'])
		//->with(['skillLevel', 'category', 'taxCode', 'taxCode.taxes'])
		;
	}

	public function wjorParts() {
		return $this->hasMany('App\WjorPart', 'wjor_id')
		//->withPivot(['net_amount', 'tax_total', 'total_amount', 'quantity', 'purchase_type'])
		//->with(['uom', 'taxCode', 'taxCode.taxes'])
		;
	}

	// public function repairOrders() {
	// 	return $this->hasMany('App\WjorRepairOrder', 'wjor_id');
	// }

	// public function parts() {
	// 	return $this->hasMany('App\WjorPart', 'wjor_id');
	// }

	public function photos() {
		return $this->hasMany('App\Attachment', 'entity_id')->where('attachment_of_id', 9120);
	}

	public function failure_photo() {
		return $this->hasOne('App\Attachment', 'entity_id')->where('attachment_of_id', 9122);
	}

	public function approvalAttachments() {
		return $this->hasMany('App\Attachment', 'entity_id')->where('attachment_of_id', 9121);
	}

	public function referenceAttachments() {
		return $this->hasMany('App\Attachment', 'entity_id')->where('attachment_of_id', 9123);
	}

	public function splitOrderType() {
		return $this->belongsTo('App\SplitOrderType', 'split_order_type_id');
	}
	public function requestType() {
		return $this->belongsTo('App\Config', 'request_type_id');
	}
	public function requestedBy() {
		return $this->belongsTo('App\User', 'created_by_id');
	}

	public static function relationships($action = '') {
		if ($action == 'index') {
			$relationships = [
				'jobOrder',
				'jobOrder.outlet',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
				'jobOrder.customer',
				'jobOrder.jobCard',
				'status',
				'serviceTypes',
			];
		} else if ($action == 'read') {
			$relationships = [
				'jobOrder',
				'jobOrder.type',
				'jobOrder.outlet',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
				'jobOrder.vehicle.bharat_stage',
				'jobOrder.vehicle.currentOwner.customer',
				'jobOrder.vehicle.currentOwner.customer.address',
				'jobOrder.vehicle.currentOwner.customer.address.country',
				'jobOrder.vehicle.currentOwner.customer.address.state',
				'jobOrder.vehicle.currentOwner.customer.address.city',
				'jobOrder.status',
				'jobOrder.customer',
				'jobOrder.jobCard',
				'complaint',
				'complaint.complaintGroup',
				'complaint.subAggregate',
				'complaint.subAggregate.aggregate',
				'fault',
				'supplier',
				'primarySegment',
				'secondarySegment',
				'operatingCondition',
				'normalRoadCondition',
				'failureRoadCondition',
				'loadCarriedType',
				'loadRange',
				'terrainAtFailure',
				'readingType',
				'status',
				'serviceTypes',
				'wjorRepairOrders',
				'wjorRepairOrders.taxes',
				'wjorRepairOrders.repairOrder',
				'wjorParts',
				'wjorParts.purchaseType',
				'wjorParts.taxes',
				'wjorParts.part',
				'photos',
				'failure_photo',
				'approvalAttachments',
				'referenceAttachments',
				'splitOrderType',
				'requestType',
				'authorizationBy',
				'failureType',
				'requestedBy.employee',
			];
		}

		return $relationships;
	}

	// Query Scopes --------------------------------------------------------------

	public function scopeFilterSearch($query, $term) {
		if (strlen($term)) {
			$query->where(function ($query) use ($term) {
				$query->orWhere('number', 'LIKE', '%' . $term . '%');
			});
		}
	}

	public function scopeFilterStatusIn($query, $statusIds) {
		$query->whereIn('status_id', $statusIds);
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

	public static function saveFromNgArray($input, $owner = null) {
		$owner = !is_null($owner) ? $owner : Auth::user();

		if (!isset($input['id']) || !$input['id']) {
			$record = new Self();
			$record->company_id = $owner->company_id;
			$record->number = rand();

		} else {
			$record = Self::find($input['id']);
			if (!$record) {
				return [
					'success' => false,
					'error' => 'Record not found',
				];
			}
		}
		$record->fill($input);
		$record->job_order_id = $input['job_order']['id'];
		$record->complaint_id = $input['complaint']['id'];
		$record->fault_id = $input['fault']['id'];
		$record->supplier_id = $input['supplier']['id'];
		$record->primary_segment_id = $input['primary_segment']['id'];
		$record->secondary_segment_id = $input['secondary_segment']['id'];
		$record->operating_condition_id = $input['operating_condition']['id'];
		$record->normal_road_condition_id = $input['normal_road_condition']['id'];
		$record->failure_road_condition_id = $input['failure_road_condition']['id'];
		// $record->load_carried_type_id = $input['load_carried_type']['id'];
		$record->load_range_id = $input['load_range']['id'];
		$record->terrain_at_failure_id = $input['terrain_at_failure']['id'];
		// $record->reading_type_id = $input['reading_type']['id'];
		$record->status_id = 9100; //New
		$record->save();
		$record->number = 'WJOR-' . $record->id;
		// $record->failure_date = ;
		$record->save();
		return [
			'success' => true,
			'message' => 'Record created successfully',
			'warranty_job_order_request' => $record,
		];

	}

	public static function saveFromFormArray($input, $owner = null) {
		try {
			DB::beginTransaction();
			$owner = !is_null($owner) ? $owner : Auth::user();

			$input['total_part_cushioning_charge'] = ($input['total_part_cushioning_charge'] != null) ? $input['total_part_cushioning_charge'] : 0;
			$input['total_part_amount'] = ($input['total_part_amount'] != null) ? $input['total_part_amount'] : 0;

			if ($input['customer_search_type'] == 'true') {
				$customer = Customer::find($input['customer_id']);
			} else {
				$customer = Customer::firstOrNew([
					'company_id' => Auth::user()->company_id,
					'code' => $input['customer_code'],
				]);
			}
			if (!$customer) {
				return [
					'success' => false,
					'error' => 'Kindly select customer',
				];

			}
			$customer->name = $input['customer_name'];
			$customer->code = $input['customer_code'];
			$customer->mobile_no = $input['customer_mobile_no'];
			$customer->email = $input['email'];
			$customer->gst_number = $input['gst_number'];
			$customer->pan_number = $input['pan_number'];
			$customer->address = $input['address_line1'] . ' ' . $input['address_line2'];
			$customer->zipcode = $input['zipcode'];
			$customer->city = $input['city_name'];
			$customer->city_id = $input['city_id'];
			$customer->state_id = $input['state_id'];
			$customer->updated_by_id = Auth::id();
			$customer->company_id = Auth::user()->company_id;
			$customer->save();

			$customer_id = $customer->id;

			$input['pincode'] = $input['zipcode'];

			$customer->saveAddress($input);

			$sold_date = null;
			if ($input['sold_date']) {
				$sold_date = date('Y-m-d', strtotime($input['sold_date']));
			}
			if ($input['vehicle_search_type'] == 'true') {
				$vehicle = Vehicle::find($input['vehicle_id']);
			} else {
				// $vehicle = Vehicle::firstOrNew([
				// 	'company_id' => Auth::user()->company_id,
				// 	'engine_number' => $input['engine_number'],
				// ]);
				$registration_number = str_replace("-", "", $input['registration_number']);

				if ($registration_number) {
					$vehicle = Vehicle::where([
						'company_id' => Auth::user()->company_id,
						'registration_number' => $registration_number,
					])->first();

					if (!$vehicle) {
						//Chassis Number
						if ($input['chassis_number']) {
							$vehicle = Vehicle::firstOrNew([
								'company_id' => Auth::user()->company_id,
								'chassis_number' => $input['chassis_number'],
							]);
						}
						//Engine Number
						else {
							$vehicle = Vehicle::firstOrNew([
								'company_id' => Auth::user()->company_id,
								'engine_number' => $input['engine_number'],
							]);
						}
					}
				} else {
					//Chassis Number
					if ($input['chassis_number']) {
						$vehicle = Vehicle::firstOrNew([
							'company_id' => Auth::user()->company_id,
							'chassis_number' => $input['chassis_number'],
						]);
					}
					//Engine Number
					else {
						$vehicle = Vehicle::firstOrNew([
							'company_id' => Auth::user()->company_id,
							'engine_number' => $input['engine_number'],
						]);
					}
				}
			}
			if (!$vehicle) {
				return [
					'success' => false,
					'error' => 'Kindly select vehicle',
				];

			}
			$registration_number = str_replace("-", "", $input['registration_number']);
			$vehicle->chassis_number = $input['chassis_number'];
			$vehicle->model_id = $input['model_id'];
			$vehicle->bharat_stage_id = $input['bharat_stage_id'];
			if ($input['vehicle_search_type'] == 'false') {
				$vehicle->registration_number = $registration_number;
				$vehicle->is_registered = $vehicle->registration_number ? 1 : 0;
			}
			if ($sold_date) {
				$vehicle->is_sold = 1;
				$vehicle->sold_date = $sold_date;
			} else {
				$vehicle->is_sold = 0;
				$vehicle->sold_date = null;
			}
			$vehicle->registration_number = $registration_number;
			$vehicle->created_by_id = Auth::id();
			$vehicle->save();

			$input['vehicle_id'] = $vehicle->id;

			$vehicle_owner = VehicleOwner::where([
				'vehicle_id' => $vehicle->id,
				'customer_id' => $customer->id,
			])->first();

			$vehicle_ownership_latest = VehicleOwner::where([
				'vehicle_id' => $vehicle->id,
			])->orderBy('ownership_id', 'desc')->first();

			$ownership_id = 8160;

			if ($vehicle_ownership_latest != null && $vehicle_ownership_latest->ownership_id == 8164) {
				return [
					'success' => false,
					'error' => 'Vehicle Owner Cannot be added, Please choose different Customer.',
				];
			} else if ($vehicle_ownership_latest != null && $vehicle_ownership_latest->ownership_id < 8164) {
				$ownership_id = $vehicle_ownership_latest->ownership_id + 1;
			}
			// dd($ownership_id, $vehicle_owner, $vehicle->id);
			if ($vehicle_owner == null) {
				//NEW OWNER
				$vehicle_owner = new VehicleOwner;
				$vehicle_owner->vehicle_id = $vehicle->id;
				$vehicle_owner->from_date = Carbon::now();
				$vehicle_owner->created_by_id = Auth::id();
				$vehicle_owner->ownership_id = $ownership_id; //8160;
			} else {
				$vehicle_owner->updated_by_id = Auth::id();
				$vehicle_owner->updated_at = Carbon::now();
				// $vehicle_owner->ownership_id = 8161;
			}

			$vehicle_owner->customer_id = $customer->id;
			$vehicle_owner->save();

			if ($input['form_type'] == "manual") {
				$job_card_number = $input['job_card_number'];
				$job_card = JobCard::where('job_card_number', $job_card_number)->first();

				$result = FinancialYear::getCurrentFinancialYear();
				if (!$result['success']) {
					return response()->json($result);
				}
				$financial_year = $result['financial_year'];
				$branch = Outlet::find($input['outlet_id']);

				$jobOrderNumber = SerialNumberGroup::generateNumber(21, $financial_year->id, $branch->state_id, $branch->id);
				if (!$jobOrderNumber['success']) {
					return [
						'success' => false,
						'error' => 'No serial number configured for Job Order. FY : ' . $financial_year->code . ' Outlet : ' . $branch->code,
					];
				}

				if (isset($input['customer_id'])) {
					$customer = json_decode($input['customer_id']);
					$customer_id = $customer;
				}

				if (!$job_card) {
					$job_order = new JobOrder;
					$job_order->company_id = $owner->company_id;
					$job_order->number = $jobOrderNumber['number'];
					$job_order->vehicle_id = $input['vehicle_id'];
					$job_order->outlet_id = $input['outlet_id'];
					$job_order->type_id = 4;
					$job_order->quote_type_id = 2;
					$job_order->km_reading_type_id = $input['reading_type_id'];
					$job_order->km_reading = $input['failed_at'];
					$job_order->hr_reading = $input['failed_at'];
					$job_order->quote_type_id = 2;
					$job_order->customer_id = $customer_id;
					$job_order->save();
					// $job_order->status_id = 8460; //Ready for Inward
					// $job_order->number = 'JO-' . $job_order->id;
					// $job_order->save();

					$job_card = new JobCard;
					$job_card->company_id = $owner->company_id;
					$job_card->dms_job_card_number = $job_card_number;
					$job_card->job_card_number = $job_card_number;
					$job_card->date = date('Y-m-d');
					$job_card->outlet_id = $input['outlet_id'];
					$job_card->created_by = Auth::id();
					$job_card->job_order_id = $job_order->id;
					$job_card->save();

					$job_order_id = $job_order->id;
				} else {
					if ($job_card->job_order_id == null) {
						$job_order = new JobOrder;
						$job_order->company_id = $owner->company_id;
						$job_order->number = $jobOrderNumber['number'];
						$job_order->vehicle_id = $input['vehicle_id'];
						$job_order->outlet_id = $input['outlet_id'];
						$job_order->type_id = 4;
						$job_order->quote_type_id = 2;
						$job_order->km_reading_type_id = $input['reading_type_id'];
						$job_order->km_reading = $input['failed_at'];
						$job_order->hr_reading = $input['failed_at'];
						$job_order->quote_type_id = 2;
						$job_order->customer_id = $customer_id;
						$job_order->save();
						$job_order_id = $job_order->id;

						$job_card = JobCard::find($job_card->id);
						$job_card->job_order_id = $job_order_id;
						$job_card->save();
					} else {
						$job_order = JobOrder::find($job_card->job_order_id);
						$job_order->company_id = $owner->company_id;
						$job_order->number = $jobOrderNumber['number'];
						$job_order->vehicle_id = $input['vehicle_id'];
						$job_order->outlet_id = $input['outlet_id'];
						$job_order->type_id = 4;
						$job_order->quote_type_id = 2;
						$job_order->km_reading_type_id = $input['reading_type_id'];
						$job_order->km_reading = $input['failed_at'];
						$job_order->hr_reading = $input['failed_at'];
						$job_order->quote_type_id = 2;
						$job_order->customer_id = $customer_id;
						$job_order->save();

						$job_order_id = $job_card->job_order_id;
					}
				}
			} else {
				$job_order_id = $input['job_order_id'];
			}

			if (!$input['id']) {
				$record = new Self();
				$record->company_id = $owner->company_id;
				$record->created_by_id = Auth::id();
				$pprNumber = SerialNumberGroup::generateNumber(30, $financial_year->id, $branch->state_id, $branch->id);
				if (!$pprNumber['success']) {
					return [
						'success' => false,
						'error' => 'No serial number configured for PPR . FY : ' . $financial_year->code . ' Outlet : ' . $branch->code,
					];
				}
				$record->number = $pprNumber['number'];

			} else {
				$record = Self::find($input['id']);
				if (!$record) {
					return [
						'success' => false,
						'error' => 'Record not found',
					];
				}
			}
			$record->job_order_id = $job_order_id;
			$record->fill($input);
			$record->status_id = 9100; //New
			// dd($record);
			$record->save();
			//$record->number = 'F20-PPR-' . $record->id;
			//$record->save();

			$service_types = json_decode($input['service_type_ids']);
			$service_type_ids = [];
			if ($service_types != null) {
				if (count($service_types) > 0) {
					foreach ($service_types as $service_type) {
						$service_type_ids[] = $service_type->id;
					}
				}
			}
			$record->serviceTypes()->sync($service_type_ids);

			if (!isset($input['wjor_repair_orders']) || !isset($input['wjor_parts'])) {
				return [
					'success' => false,
					'error' => 'One Part Or Labour atleast should be selected.',
				];
			}

			if (isset($input['wjor_repair_orders'])) {
				$wjorRepair_orders = json_decode($input['wjor_repair_orders']);
				$record->syncRepairOrders($wjorRepair_orders);
			}

			if (isset($input['wjor_parts'])) {
				$wjorPartsInput = json_decode($input['wjor_parts']);
				$record->syncParts($wjorPartsInput);
			}

			//REMOVE ATTACHMENTS
			if (isset($input['attachment_removal_ids'])) {
				$attachment_removal_ids = json_decode($input['attachment_removal_ids']);
				if (!empty($attachment_removal_ids)) {
					Attachment::whereIn('id', $attachment_removal_ids)->forceDelete();
				}
			}
			//SAVE ATTACHMENTS
			$attachement_path = storage_path('app/public/wjor/');
			Storage::makeDirectory($attachement_path, 0777);
			if (isset($input['failure_report_file'])) {

				$value = rand(1, 100);
				$image = $input['failure_report_file'];

				$file_name_with_extension = $image->getClientOriginalName();
				$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
				$extension = $image->getClientOriginalExtension();
				// dd($file_name, $extension);
				//ISSUE : file name should be stored
				$name = $record->id . '_' . $file_name . '_failure_report_' . rand(10, 1000) . '.' . $extension;

				$image->move($attachement_path, $name);
				// $attachement = new Attachment;

				$attachement = Attachment::firstOrNew([
					'attachment_of_id' => 9122,
					'attachment_type_id' => 244,
					'entity_id' => $record->id,
				]);
				$attachement->attachment_of_id = 9122;
				$attachement->attachment_type_id = 244;
				$attachement->entity_id = $record->id;
				$attachement->name = $name;
				$attachement->path = null;
				$attachement->save();
			}
			if (isset($input['reference_attachment'])) {
				foreach ($input['reference_attachment'] as $key => $photo) {
					$value = rand(1, 100);
					$image = $photo;

					$file_name_with_extension = $image->getClientOriginalName();
					$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
					$extension = $image->getClientOriginalExtension();
					// dd($file_name, $extension);
					//ISSUE : file name should be stored
					$name = $record->id . '_' . $file_name . '_' . rand(10, 1000) . '.' . $extension;

					$photo->move($attachement_path, $name);
					$attachement = new Attachment;
					$attachement->attachment_of_id = 9123;
					$attachement->attachment_type_id = 244;
					$attachement->entity_id = $record->id;
					$attachement->name = $name;
					$attachement->path = $input['reference_attachment_description'][$key];
					$attachement->save();
				}
			}
			if (isset($input['photos'])) {
				foreach ($input['photos'] as $key => $photo) {
					$value = rand(1, 100);
					$image = $photo;

					$file_name_with_extension = $image->getClientOriginalName();
					$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
					$extension = $image->getClientOriginalExtension();
					// dd($file_name, $extension);
					//ISSUE : file name should be stored
					$name = $record->id . '_' . $file_name . '_' . rand(10, 1000) . '.' . $extension;

					$photo->move($attachement_path, $name);
					$attachement = new Attachment;
					$attachement->attachment_of_id = 9120;
					$attachement->attachment_type_id = 244;
					$attachement->entity_id = $record->id;
					$attachement->name = $name;
					$attachement->path = $input['attachment_descriptions'][$key];
					$attachement->save();
				}
			}

			if (!$input['id']) {
				WarrantyJobOrderRequest::where('status_id', 9104)->where('created_by_id', Auth::id())->forceDelete();
			}
			DB::commit();

			return [
				'success' => true,
				'message' => 'Record created successfully',
				'warranty_job_order_request' => $record,
			];
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}

	}

	public static function saveTempData($input) {
		try {
			DB::beginTransaction();
			$owner = Auth::user();

			$customer_id = null;
			$customer = null;
			if ($input['customer_id'] != null) {
				$customer = json_decode($input['customer_id']);
				$customer_id = $customer;
			}

			$input['customer_code'] = ($input['customer_code']) ? $input['customer_code'] : 'RANDCUSTOMER';
			$input['customer_name'] = ($input['customer_name']) ? $input['customer_name'] : 'RANDCUSTOMER';
			$input['address_line1'] = ($input['address_line1']) ? $input['address_line1'] : '';

			if ($input['customer_search_type'] == 'true') {
				if ($input['customer_id'] != null) {
					$customer = Customer::findOrNew($input['customer_id']);
				} else {
					$customer = Customer::firstOrNew([
						'company_id' => Auth::user()->company_id,
						'code' => $input['customer_code'],
					]);
				}
			} else {
				$customer = Customer::firstOrNew([
					'company_id' => Auth::user()->company_id,
					'code' => $input['customer_code'],
				]);
			}
			if ($customer != null) {
				$customer->name = $input['customer_name'];
				$customer->code = $input['customer_code'];
				$customer->company_id = Auth::user()->company_id;
				$customer->mobile_no = $input['customer_mobile_no'];
				$customer->email = $input['email'];
				$customer->gst_number = $input['gst_number'];
				$customer->pan_number = $input['pan_number'];
				$customer->address = $input['address_line1'] . ' ' . $input['address_line2'];
				$customer->zipcode = $input['zipcode'];
				$customer->city = $input['city_name'];
				$customer->city_id = $input['city_id'];
				$customer->state_id = $input['state_id'];
				$customer->updated_by_id = Auth::id();
				$customer->save();

				$customer_id = $customer->id;

				$input['pincode'] = $input['zipcode'];

				$customer->saveAddress($input);
			}

			$sold_date = null;
			$vehicle_id = null;

			$chassis_number = ($input['chassis_number']) ? $input['chassis_number'] : null;
			$engine_number = ($input['engine_number']) ? $input['engine_number'] : null;
			$registration_number = ($input['registration_number']) ? $input['registration_number'] : null;

			if ($input['sold_date']) {
				$sold_date = date('Y-m-d', strtotime($input['sold_date']));
			}

			if ($input['vehicle_search_type'] == 'true') {
				$vehicle_id = $input['vehicle_id'];
				if ($vehicle_id != null) {
					$vehicle = Vehicle::find($input['vehicle_id']);
				} else {
					$vehicle = new Vehicle;
				}
			} else {
				if ($chassis_number != null) {
					$vehicle = Vehicle::firstOrNew([
						'company_id' => Auth::user()->company_id,
						'chassis_number' => $chassis_number,
					]);
				} elseif ($engine_number != null) {
					$vehicle = Vehicle::firstOrNew([
						'company_id' => Auth::user()->company_id,
						'engine_number' => $engine_number,
					]);
				} elseif ($registration_number != null) {
					$vehicle = Vehicle::firstOrNew([
						'company_id' => Auth::user()->company_id,
						'registration_number' => $registration_number,
					]);
				} else {
					$vehicle = new Vehicle;
				}
			}

			if ($vehicle != null) {

				$vehicle->chassis_number = $input['chassis_number'];
				$vehicle->model_id = $input['model_id'];
				$vehicle->bharat_stage_id = $input['bharat_stage_id'];
				if ($input['vehicle_search_type'] == 'false') {
					$vehicle->registration_number = $input['registration_number'];
					$vehicle->is_registered = $vehicle->registration_number ? 1 : 0;
				}
				if ($sold_date) {
					$vehicle->is_sold = 1;
					$vehicle->sold_date = $sold_date;
				} else {
					$vehicle->is_sold = 0;
					$vehicle->sold_date = null;
				}
				$vehicle->created_by_id = Auth::id();
				$vehicle->company_id = Auth::user()->company_id;
				$vehicle->save();

				$input['vehicle_id'] = $vehicle->id;
			}

			if ($vehicle && $customer) {
				$vehicle_owner = VehicleOwner::where([
					'vehicle_id' => $vehicle->id,
					'customer_id' => $customer->id,
				])->first();
				$vehicle_ownership_latest = VehicleOwner::where([
					'vehicle_id' => $vehicle->id,
				])->orderBy('ownership_id', 'desc')->first();
				$ownership_id = 8160;
				if ($vehicle_ownership_latest != null && $vehicle_ownership_latest->ownership_id == 8164) {
					return [
						'success' => false,
						'error' => 'Vehicle Owner Cannot be added, Please choose different Customer.',
					];
				} else if ($vehicle_ownership_latest != null && $vehicle_ownership_latest->ownership_id < 8164) {
					$ownership_id = $vehicle_ownership_latest->ownership_id + 1;
				}

				if ($vehicle_owner == null) {
					//NEW OWNER
					$vehicle_owner = new VehicleOwner;
					$vehicle_owner->vehicle_id = $vehicle->id;
					$vehicle_owner->from_date = Carbon::now();
					$vehicle_owner->created_by_id = Auth::id();
					$vehicle_owner->ownership_id = $ownership_id; //8160;
				} else {
					$vehicle_owner->updated_by_id = Auth::id();
					$vehicle_owner->updated_at = Carbon::now();
				}

				$vehicle_owner->customer_id = $customer->id;
				$vehicle_owner->save();
			}

			$job_card_number = 'XYZ123JOBCARDNO'; // $input['job_card_number'];
			$job_card = JobCard::where('job_card_number', $job_card_number)->first();

			$result = FinancialYear::getCurrentFinancialYear();
			if (!$result['success']) {
				return response()->json($result);
			}
			$financial_year = $result['financial_year'];
			if ($input['outlet_id'] == null) {
				$input['outlet_id'] = Outlet::first()->id;
			}
			$branch = Outlet::find($input['outlet_id']);

			$jobOrderNumber = SerialNumberGroup::generateNumber(21, $financial_year->id, $branch->state_id, $branch->id);
			if (!$jobOrderNumber['success']) {
				return [
					'success' => false,
					'error' => 'No serial number configured for Job Order. FY : ' . $financial_year->code . ' Outlet : ' . $branch->code,
				];
			}

			$input['reading_type_id'] = (!isset($input['reading_type_id'])) ? 8040 : $input['reading_type_id'];

			if (!$job_card) {
				$job_order = new JobOrder;
				$job_order->company_id = $owner->company_id;
				$job_order->number = $jobOrderNumber['number'];
				$job_order->vehicle_id = $input['vehicle_id'];
				$job_order->outlet_id = $input['outlet_id'];
				$job_order->type_id = 4;
				$job_order->km_reading_type_id = $input['reading_type_id'];
				$job_order->km_reading = $input['failed_at'];
				$job_order->hr_reading = $input['failed_at'];
				$job_order->quote_type_id = 2;
				$job_order->customer_id = $customer_id;
				$job_order->save();

				$job_card = new JobCard;
				$job_card->company_id = $owner->company_id;
				$job_card->dms_job_card_number = $job_card_number;
				$job_card->job_card_number = $job_card_number;
				$job_card->date = date('Y-m-d');
				$job_card->outlet_id = $input['outlet_id'];
				$job_card->created_by = Auth::id();
				$job_card->job_order_id = $job_order->id;
				$job_card->save();

				$job_order_id = $job_order->id;
			} else {
				if ($job_card->job_order_id == null) {
					$job_order = new JobOrder;
					$job_order->company_id = $owner->company_id;
					$job_order->number = $jobOrderNumber['number'];
					$job_order->vehicle_id = $input['vehicle_id'];
					$job_order->outlet_id = $input['outlet_id'];
					$job_order->type_id = 4;
					$job_order->quote_type_id = 2;
					$job_order->km_reading_type_id = $input['reading_type_id'];
					$job_order->km_reading = $input['failed_at'];
					$job_order->hr_reading = $input['failed_at'];
					$job_order->quote_type_id = 2;
					$job_order->customer_id = $customer_id;
					$job_order->save();
					$job_order_id = $job_order->id;

					$job_card = JobCard::find($job_card->id);
					$job_card->job_order_id = $job_order_id;
					$job_card->save();
				} else {
					$job_order = JobOrder::find($job_card->job_order_id);
					$job_order->company_id = $owner->company_id;
					$job_order->number = $jobOrderNumber['number'];
					$job_order->vehicle_id = $input['vehicle_id'];
					$job_order->outlet_id = $input['outlet_id'];
					$job_order->type_id = 4;
					$job_order->quote_type_id = 2;
					$job_order->km_reading_type_id = $input['reading_type_id'];
					$job_order->km_reading = $input['failed_at'];
					$job_order->hr_reading = $input['failed_at'];
					$job_order->quote_type_id = 2;
					$job_order->customer_id = $customer_id;
					$job_order->save();

					$job_order_id = $job_card->job_order_id;
				}
			}

			$record = WarrantyJobOrderRequest::where('status_id', 9104)->where('created_by_id', Auth::id())->first();
			if (!$record) {
				$record = new WarrantyJobOrderRequest;
			}
			$record->company_id = $owner->company_id;
			$record->created_by_id = Auth::id();
			$pprNumber = SerialNumberGroup::generateNumber(30, $financial_year->id, $branch->state_id, $branch->id);
			if (!$pprNumber['success']) {
				return [
					'success' => false,
					'error' => 'No serial number configured for PPR . FY : ' . $financial_year->code . ' Outlet : ' . $branch->code,
				];
			}
			$record->number = $pprNumber['number'];

			$record->job_order_id = $job_order_id;
			if (!isset($input['total_part_cushioning_charge'])) {
				$input['total_part_cushioning_charge'] = 0;
			}
			if (!isset($input['total_part_amount'])) {
				$input['total_part_amount'] = 0;
			}
			$input['total_part_cushioning_charge'] = ($input['total_part_cushioning_charge'] != null) ? $input['total_part_cushioning_charge'] : 0;
			$input['total_part_amount'] = ($input['total_part_amount'] != null) ? $input['total_part_amount'] : 0;
			$input['failure_date'] = ($input['failure_date'] == null) ? Carbon::now() : $input['failure_date'];
			$input['unit_serial_number'] = ($input['unit_serial_number'] == null) ? '123' : $input['unit_serial_number'];

			$record->fill($input);
			$record->status_id = 9104; //Temporary Save
			$record->save();

			$service_types = json_decode($input['service_type_ids']);
			$service_type_ids = [];
			if ($service_types != null) {
				if (count($service_types) > 0) {
					foreach ($service_types as $service_type) {
						$service_type_ids[] = $service_type->id;
					}
				}
			}
			$record->serviceTypes()->sync($service_type_ids);

			if (isset($input['wjor_repair_orders'])) {
				$wjorRepair_orders = json_decode($input['wjor_repair_orders']);
				$record->syncRepairOrders($wjorRepair_orders);
			}

			if (isset($input['wjor_parts'])) {
				$wjorPartsInput = json_decode($input['wjor_parts']);
				$record->syncParts($wjorPartsInput);
			}

			//SAVE ATTACHMENTS
			$attachement_path = storage_path('app/public/wjor/');
			Storage::makeDirectory($attachement_path, 0777);
			if (isset($input['failure_report_file'])) {

				$value = rand(1, 100);
				$image = $input['failure_report_file'];

				$file_name_with_extension = $image->getClientOriginalName();
				$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
				$extension = $image->getClientOriginalExtension();
				// dd($file_name, $extension);
				//ISSUE : file name should be stored
				$name = $record->id . '_' . $file_name . '_failure_report_' . rand(10, 1000) . '.' . $extension;

				$image->move($attachement_path, $name);
				// $attachement = new Attachment;

				$attachement = Attachment::firstOrNew([
					'attachment_of_id' => 9122,
					'attachment_type_id' => 244,
					'entity_id' => $record->id,
				]);
				$attachement->attachment_of_id = 9122;
				$attachement->attachment_type_id = 244;
				$attachement->entity_id = $record->id;
				$attachement->name = $name;
				$attachement->path = null;
				$attachement->save();
			}
			if (isset($input['reference_attachment'])) {
				foreach ($input['reference_attachment'] as $key => $photo) {
					$value = rand(1, 100);
					$image = $photo;

					$file_name_with_extension = $image->getClientOriginalName();
					$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
					$extension = $image->getClientOriginalExtension();
					// dd($file_name, $extension);
					//ISSUE : file name should be stored
					$name = $record->id . '_' . $file_name . '_' . rand(10, 1000) . '.' . $extension;

					$photo->move($attachement_path, $name);
					$attachement = new Attachment;
					$attachement->attachment_of_id = 9123;
					$attachement->attachment_type_id = 244;
					$attachement->entity_id = $record->id;
					$attachement->name = $name;
					$attachement->path = $input['reference_attachment_description'][$key];
					$attachement->save();
				}
			}
			if (isset($input['photos'])) {
				foreach ($input['photos'] as $key => $photo) {
					$value = rand(1, 100);
					$image = $photo;

					$file_name_with_extension = $image->getClientOriginalName();
					$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
					$extension = $image->getClientOriginalExtension();
					// dd($file_name, $extension);
					//ISSUE : file name should be stored
					$name = $record->id . '_' . $file_name . '_' . rand(10, 1000) . '.' . $extension;

					$photo->move($attachement_path, $name);
					$attachement = new Attachment;
					$attachement->attachment_of_id = 9120;
					$attachement->attachment_type_id = 244;
					$attachement->entity_id = $record->id;
					$attachement->name = $name;
					$attachement->path = $input['attachment_descriptions'][$key];
					$attachement->save();
				}
			}
			DB::commit();

			return [
				'success' => true,
				'message' => 'Temporary Data Saved',
				'warranty_job_order_request' => $record,
			];
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function syncRepairOrders($wjor_repair_orders) {
		WjorRepairOrder::where('wjor_id', $this->id)->delete();
		foreach ($wjor_repair_orders as $wjor_repair_order_input) {
			$wjorRepairOrder = new WjorRepairOrder;
			$wjorRepairOrder->wjor_id = $this->id;
			$wjorRepairOrder->repair_order_id = $wjor_repair_order_input->repair_order->id;
			$wjorRepairOrder->net_amount = $wjor_repair_order_input->net_amount;
			$wjorRepairOrder->tax_total = $wjor_repair_order_input->tax_total;
			$wjorRepairOrder->total_amount = $wjor_repair_order_input->total_amount;
			$wjorRepairOrder->save();

			$taxes = [];
			foreach ($wjor_repair_order_input->taxes as $key => $tax) {
				$taxes[$tax->id] = [
					'percentage' => $tax->pivot->percentage,
					'amount' => $tax->pivot->amount,
				];
			}

			$wjorRepairOrder->taxes()->sync($taxes);

		}

	}

	public function syncParts($wjorParts) {
		WjorPart::where('wjor_id', $this->id)->delete();
		foreach ($wjorParts as $wjorPartInput) {
			// dump($wjorPartInput);
			// dump($wjorPartInput->purchase_type);
			$wjorPart = new WjorPart;
			if (gettype($wjorPartInput->purchase_type) == "object") {
				$purchase_type = $wjorPartInput->purchase_type->id;
			} else {
				$purchase_type = $wjorPartInput->purchase_type;
			}
			if (!isset($wjorPartInput->handling_charge_percentage)) {
				$wjorPartInput->handling_charge_percentage = 0;
			}
			$wjorPart->wjor_id = $this->id;
			$wjorPart->part_id = $wjorPartInput->part->id;
			$wjorPart->purchase_type = $purchase_type; // $wjorPartInput->purchase_type;
			$wjorPart->qty = $wjorPartInput->qty;
			$wjorPart->rate = $wjorPartInput->rate;
			$wjorPart->net_amount = $wjorPartInput->net_amount;
			$wjorPart->handling_charge_percentage = $wjorPartInput->handling_charge_percentage;
			$wjorPart->handling_charge = $wjorPartInput->handling_charge;
			$wjorPart->tax_total = $wjorPartInput->tax_total;
			$wjorPart->total_amount = $wjorPartInput->total_amount;
			$wjorPart->save();

			$taxes = [];
			foreach ($wjorPartInput->taxes as $key => $tax) {
				$taxes[$tax->id] = [
					'percentage' => $tax->pivot->percentage,
					'amount' => $tax->pivot->amount,
				];
			}
			// $wjorPart->taxes()->sync($taxes);
		}
	}

	public function generatePDF() {
		/*foreach ($this->photos as $photo) {
			dump(url('storage/app/wjor/' . $photo->name));
		}*/
		// File::delete(storage_path('app/public/wjor-pdfs/' . $this->number . '.pdf'));

		// setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])
		// 	->
		$pdf = PDF::loadView('pdf-gigo/wjor', [
			'wjor' => $this,
			'company' => $this->company,
			'outlet' => $this->jobOrder->outlet,
			'title' => 'Product Performance Report',
		]);
		$path = storage_path('app/public/wjor-pdfs/');
		if (!file_exists($path)) {
			File::makeDirectory($path, $mode = 0777, true, true);
		}

		return $pdf->save(storage_path('app/public/wjor-pdfs/' . $this->number . '.pdf'));
	}

	public function loadBusiness($business_code) {
		$result = $this->jobOrder->outlet->getBusiness(['businessName' => $business_code]);
		if (!$result['success']) {
			return response()->json($result);
		}
		$this->jobOrder->outlet->business = $result['business'];

	}
}
