<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Abs\SerialNumberPkg\SerialNumberGroup;
use App\Attachment;
use App\BaseModel;
use App\FinancialYear;
use App\JobCard;
use App\Outlet;
use App\WjorPart;
use App\WjorRepairOrder;
use Auth;
use DB;
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

	public function approvalAttachments() {
		return $this->hasMany('App\Attachment', 'entity_id')->where('attachment_of_id', 9121);
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
				'jobOrder.status',
				'jobOrder.customer',
				'jobOrder.jobCard',
				'complaint',
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
				'approvalAttachments',
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
			if ($input['form_type'] == "manual") {
				$job_card_number = $input['job_card_number'];
				$job_card = JobCard::where('job_card_number', $job_card_number)->first();

				$result = FinancialYear::getCurrentFinancialYear();
				if (!$result['success']) {
					return response()->json($result);
				}
				$financial_year = $result['financial_year'];
				$branch = Outlet::find($input['outlet_id']);

				$generateJONumber = SerialNumberGroup::generateNumber(21, $financial_year->id, $branch->state_id, $branch->id);

				$customer = json_decode($input['customer_id']);

				if (!$job_card) {
					$job_order = new JobOrder;
					$job_order->company_id = $owner->company_id;
					$job_order->number = $generateJONumber['number'];
					$job_order->vehicle_id = $input['vehicle_id'];
					$job_order->outlet_id = $input['outlet_id'];
					$job_order->type_id = 4;
					$job_order->quote_type_id = 2;
					$job_order->km_reading_type_id = $input['reading_type_id'];
					$job_order->km_reading = $input['failed_at'];
					$job_order->hr_reading = $input['failed_at'];
					$job_order->quote_type_id = 2;
					$job_order->customer_id = $customer->id;
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
					$job_order_id = $job_card->job_order_id;
				}
			} else {
				$job_order_id = $input['job_order_id'];
			}

			if (!$input['id']) {
				$record = new Self();
				$record->company_id = $owner->company_id;
				$record->created_by_id = Auth::id();
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
			$record->job_order_id = $job_order_id;
			$record->fill($input);
			$record->status_id = 9100; //New
			// dd($record);
			$record->save();
			$record->number = 'WJOR-' . $record->id;
			$record->save();

			$service_types = json_decode($input['service_type_ids']);
			$service_type_ids = [];
			if (count($service_types) > 0) {
				foreach ($service_types as $service_type) {
					$service_type_ids[] = $service_type->id;
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
			$wjorPart = new WjorPart;
			$wjorPart->wjor_id = $this->id;
			$wjorPart->part_id = $wjorPartInput->part->id;
			$wjorPart->purchase_type = $wjorPartInput->purchase_type;
			$wjorPart->qty = $wjorPartInput->qty;
			$wjorPart->rate = $wjorPartInput->rate;
			$wjorPart->net_amount = $wjorPartInput->net_amount;
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
		// foreach ($this->photos as $photo) {
		// 	dump(url('storage/app/wjor/' . $photo->name));
		// }

		// dd($this->photos);
		$pdf = PDF::loadView('pdf-gigo/wjor', [
			'wjor' => $this,
			'company' => $this->company,
			'outlet' => $this->jobOrder->outlet,
			'title' => 'Warranty Job Order Request',
		]);
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
