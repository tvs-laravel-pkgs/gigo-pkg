<?php

namespace Abs\GigoPkg\Api;

use Abs\GigoPkg\JobOrderEstimate;
use Abs\GigoPkg\RepairOrder;
use Abs\GigoPkg\ServiceOrderType;
use Abs\GigoPkg\ShortUrl;
use Abs\SerialNumberPkg\SerialNumberGroup;
use Abs\TaxPkg\Tax;
use App\Attachment;
use App\GigoManualInvoice;
use App\Campaign;
use App\Config;
use App\Country;
use App\Customer;
use App\CustomerVoice;
use App\Employee;
use App\Entity;
use App\EstimationType;
use App\FinancialYear;
use App\FloatingGatePass;
use App\FloatStock;
use App\GateLog;
use App\GatePass;
use App\GigoInvoice;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WpoSoapController;
use App\JobCard;
use App\JobOrder;
use App\JobOrderCampaign;
use App\JobOrderCampaignChassisNumber;
use App\JobOrderIssuedPart;
use App\JobOrderPart;
use App\JobOrderRepairOrder;
use App\JobOrderReturnedPart;
use App\Receipt;
use App\Otp;
use App\Payment;
use App\Outlet;
use App\Part;
use App\PartsGrnDetail;
use App\PartsRequest;
use App\PartsRequestDetail;
use App\PartsRequestPart;
use App\PartStock;
use App\QuoteType;
use App\RepairOrderType;
use App\RoadTestGatePass;
use App\ServiceType;
use App\SplitOrderType;
use App\State;
use App\TradePlateNumber;
use App\User;
use App\Vehicle;
use App\VehicleInspectionItem;
use App\VehicleInspectionItemGroup;
use App\VehicleInventoryItem;
use App\VehicleModel;
use App\VehicleOwner;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use File;
use Illuminate\Http\Request;
use Storage;
use Validator;

class ManualVehicleDeliveryController extends Controller {
	public $successStatus = 200;

	public function __construct(WpoSoapController $getSoap = null) {
		$this->getSoap = $getSoap;
	}

	public function getGateInList(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				'service_advisor_id' => [
					'required',
					'exists:users,id',
					'integer',
				],
				'offset' => 'nullable|numeric',
				'limit' => 'nullable|numeric',
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$vehicle_inward_list_get = JobOrder::join('gate_logs', 'gate_logs.job_order_id', 'job_orders.id')
				->leftJoin('vehicles', 'job_orders.vehicle_id', 'vehicles.id')
				->leftJoin('vehicle_owners', function ($join) {
					$join->on('vehicle_owners.vehicle_id', 'job_orders.vehicle_id')
						->whereRaw('vehicle_owners.from_date = (select MAX(vehicle_owners1.from_date) from vehicle_owners as vehicle_owners1 where vehicle_owners1.vehicle_id = job_orders.vehicle_id)');
				})
				->leftJoin('customers', 'customers.id', 'vehicle_owners.customer_id')
				->leftJoin('models', 'models.id', 'vehicles.model_id')
				->leftJoin('amc_members', 'amc_members.vehicle_id', 'vehicles.id')
				->leftJoin('amc_policies', 'amc_policies.id', 'amc_members.policy_id')
				->join('configs as status', 'status.id', 'job_orders.status_id')
				->select([
					'job_orders.id',
					DB::raw('IF(vehicles.is_registered = 1,"Registered Vehicle","Un-Registered Vehicle") as registration_type'),
					'vehicles.registration_number',
					'vehicles.chassis_number',
					'vehicles.engine_number',
					'models.model_number',
					'gate_logs.number',
					'job_orders.status_id',
					DB::raw('DATE_FORMAT(gate_logs.gate_in_date,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(gate_logs.gate_in_date,"%h:%i %p") as time'),
					'job_orders.driver_name',
					'job_orders.is_customer_agreed',
					'job_orders.driver_mobile_number as driver_mobile_number',
					DB::raw('GROUP_CONCAT(amc_policies.name) as amc_policies'),
					'status.name as status_name',
					'customers.name as customer_name',
				])
				->where(function ($query) use ($request) {
					if (!empty($request->search_key)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('customers.name', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('vehicles.chassis_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('vehicles.engine_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('models.model_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('amc_policies.name', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('gate_logs.number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('status.name', 'LIKE', '%' . $request->search_key . '%')
						;
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->gate_in_date)) {
						$query->whereDate('gate_logs.gate_in_date', date('Y-m-d', strtotime($request->gate_in_date)));
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->reg_no)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->reg_no . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->membership)) {
						$query->where('amc_policies.name', 'LIKE', '%' . $request->membership . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->gate_in_no)) {
						$query->where('gate_logs.number', 'LIKE', '%' . $request->gate_in_no . '%');
					}
				})
				->where(function ($query) use ($request) {
					if ($request->registration_type == '1' || $request->registration_type == '0') {
						$query->where('vehicles.is_registered', $request->registration_type);
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->customer_id)) {
						$query->where('vehicle_owners.customer_id', $request->customer_id);
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->model_id)) {
						$query->where('vehicles.model_id', $request->model_id);
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->status_id)) {
						$query->where('job_orders.status_id', $request->status_id);
					}
				})
				->where('job_orders.company_id', Auth::user()->company_id)
			;
			/*if (!Entrust::can('view-overall-outlets-vehicle-inward')) {
				if (Entrust::can('view-mapped-outlet-vehicle-inward')) {
					$vehicle_inward_list_get->whereIn('job_orders.outlet_id', Auth::user()->employee->outlets->pluck('id')->toArray());
				} else {
					$vehicle_inward_list_get->where('job_orders.outlet_id', Auth::user()->employee->outlet_id)
						->whereRaw("IF (`job_orders`.`status_id` = '8460', `job_orders`.`service_advisor_id` IS  NULL, `job_orders`.`service_advisor_id` = '" . $request->service_advisor_id . "')");
				}
			}*/
			if (!Entrust::can('view-overall-outlets-vehicle-inward')) {
				if (Entrust::can('view-mapped-outlet-vehicle-inward')) {
					$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
					array_push($outlet_ids, Auth::user()->employee->outlet_id);
					$vehicle_inward_list_get->whereIn('job_orders.outlet_id', $outlet_ids);
				} elseif (Entrust::can('view-own-outlet-vehicle-inward')) {
					$vehicle_inward_list_get->where('job_orders.outlet_id', Auth::user()->employee->outlet_id)
						->whereRaw("IF (`job_orders`.`status_id` = '8460', `job_orders`.`service_advisor_id` IS  NULL, `job_orders`.`service_advisor_id` = '" . $request->service_advisor_id . "')");
				} else {
					$vehicle_inward_list_get->where('job_orders.service_advisor_id', Auth::user()->id);
				}
			}

			$vehicle_inward_list_get->groupBy('job_orders.id');
			$vehicle_inward_list_get->orderBy('job_orders.created_at', 'DESC');

			$total_records = $vehicle_inward_list_get->get()->count();

			if ($request->offset) {
				$vehicle_inward_list_get->offset($request->offset);
			}
			if ($request->limit) {
				$vehicle_inward_list_get->limit($request->limit);
			}

			$gate_logs = $vehicle_inward_list_get->get();

			$params = [
				'config_type_id' => 49,
				'add_default' => true,
				'default_text' => "Select Status",
			];
			$extras = [
				'registration_type_list' => [
					['id' => '', 'name' => 'Select Registration Type'],
					['id' => '1', 'name' => 'Registered Vehicle'],
					['id' => '0', 'name' => 'Un-Registered Vehicle'],
				],
				'status_list' => Config::getDropDownList($params),
			];

			return response()->json([
				'success' => true,
				'gate_logs' => $gate_logs,
				'extras' => $extras,
				'total_records' => $total_records,
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

	public function getFormData(Request $request) {
		// dd($request->all());
		$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'vehicle.currentOwner.customer',
				'vehicle.currentOwner.customer.address',
				'vehicle.currentOwner.customer.address.country',
				'vehicle.currentOwner.customer.address.state',
				'vehicle.currentOwner.customer.address.city',
				'vehicle.currentOwner.ownershipType',
				'gateLog',
				'gateLog.createdBy',
				'gateLog.driverAttachment',
				'gateLog.kmAttachment',
				'gateLog.vehicleAttachment',
				'gateLog.chassisAttachment',
				'manualDeliveryLabourInvoice',
				'manualDeliveryPartsInvoice',
				'manualDeliveryReceipt',
				'status',
		])
			->select([
				'job_orders.*',
				DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
			])
			->find($request->id);

		// dd($job_order);

		if (!$job_order) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => [
					'Job Order Not Found!',
				],
			]);
		}

		$this->data['success'] = true;
		$this->data['job_order'] = $job_order;

        $extras = [
            'purpose_list' => Config::getDropDownList([
				'config_type_id' => 421,
				'orderBy' => 'id',
                'default_text' => 'Select Purpose',
			]),
			'parts_category_list' => Config::getDropDownList([
				'config_type_id' => 422,
				'orderBy' => 'id',
                'default_text' => 'Select Category',
            ]),
		];
		
		$this->data['extras'] = $extras;

        return response()->json($this->data);
        
	}
	
	public function save(Request $request) {
        // dd($request->all());
		try {
			$error_messages = [
				'vehicle_delivery_request_remarks.required_if' => "Vehicle Delivery Request Remarks is required",
			];
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				// 'invoice_number' => [
				// 	'required',
				// ],
				'invoice_date' => [
					'required',
				],
				// 'invoice_amount' => [
				// 	'required',
				// ],
				'labour_invoice_number' => [
					'required',
				],
				'labour_amount' => [
					'required',
				],
				'parts_invoice_number' => [
					'required',
				],
				'parts_amount' => [
					'required',
				],
				'receipt_number' => [
					'required_if:vehicle_payment_status,==,1',
				],
				'receipt_date' => [
					'required_if:vehicle_payment_status,==,1',
				],
				'receipt_amount' => [
					'required_if:vehicle_payment_status,==,1',
				],
				'vehicle_delivery_request_remarks' => [
					'required_if:vehicle_payment_status,==,0',
				],
			], $error_messages);
	
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$job_order = JobOrder::find($request->job_order_id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}

			DB::beginTransaction();

			$job_order->vehicle_payment_status = $request->vehicle_payment_status;
			if($request->vehicle_payment_status == 1)
			{
				$job_order->vehicle_delivery_requester_id = NULL;
				$job_order->vehicle_delivery_request_remarks = NULL;
				$job_order->status_id = 8478;
				$payment_status_id = 2;

				$message = "Vehicle GateOut Pending";
			}
			else
			{
				$job_order->vehicle_delivery_requester_id = Auth::user()->id;
				$job_order->vehicle_delivery_request_remarks = $request->vehicle_delivery_request_remarks;
				$job_order->status_id = 8477;
				$payment_status_id = 1;

				$message = "Manual Vehicle Delivery saved Successfully!";

			}
			$job_order->save();

			//Delete previous receipt
			$remove_receipt = Receipt::where('receipt_of_id',7622)->where('entity_id',$job_order->id)->forceDelete();

			//Delete previous Invoice
			$remove_invoice = GigoManualInvoice::where('invoiceable_type','App\JobOrder')->where('invoiceable_id',$job_order->id)->forceDelete();

			$receipt_id = NULL;
			if($payment_status_id == 2)
			{
				//Save Receipt
				$customer = Customer::find($job_order->customer_id);

				$receipt = new Receipt;
				$receipt->company_id = Auth::user()->company_id;
				$receipt->temporary_receipt_no = $request->receipt_number;
				$receipt->date = date('Y-m-d',strtotime($request->receipt_date));
				$receipt->outlet_id = $job_order->outlet_id;
				$receipt->receipt_of_id = 7622;
				$receipt->entity_id = $job_order->id;
				$receipt->permanent_receipt_no = $request->receipt_number;
				$receipt->amount = $request->receipt_amount;
				$receipt->settled_amount = $request->receipt_amount;
				$receipt->created_at = Carbon::now();

				$customer->receipt()->save($receipt);

				$receipt_id = $customer->receipt ? $customer->receipt[0] ? $customer->receipt[0]->id : NULL : NULL;

				//Save Payment	
				$payment = new Payment;
				// dd($payment);
				$payment->entity_type_id = 8434;
				$payment->entity_id = $job_order->id;
				$payment->received_amount = $request->receipt_amount;
				$payment->receipt_id = $receipt_id;
				$job_order->payment()->save($payment);

				// dd($job_order->payment);
			}

			//Save Labour Invoice Details
			$invoice_detail = new GigoManualInvoice;
			$invoice_detail->number = $request->labour_invoice_number;
			$invoice_detail->invoice_type_id = 1;
			$invoice_detail->outlet_id = $job_order->outlet_id;
			$invoice_detail->customer_id = $job_order->customer_id;
			$invoice_detail->amount = $request->labour_amount;
			$invoice_detail->invoice_date =date('Y-m-d',strtotime($request->invoice_date));
			$invoice_detail->payment_status_id = $payment_status_id;
			$invoice_detail->created_by_id = Auth::user()->id;
			$invoice_detail->created_at = Carbon::now();
			$invoice_detail->receipt_id = $receipt_id;

			$job_order->invoice()->save($invoice_detail);

			// dump($job_order->invoice);

			//Save Parts Invoice Details
			$invoice_detail = new GigoManualInvoice;
			$invoice_detail->number = $request->parts_invoice_number;
			$invoice_detail->customer_id = 45;
			$invoice_detail->invoice_type_id = 2;
			$invoice_detail->amount = $request->parts_amount;
			$invoice_detail->outlet_id = $job_order->outlet_id;
			$invoice_detail->customer_id = $job_order->customer_id;
			$invoice_detail->invoice_date =date('Y-m-d',strtotime($request->invoice_date));
			$invoice_detail->payment_status_id = $payment_status_id;
			$invoice_detail->created_by_id = Auth::user()->id;
			$invoice_detail->created_at = Carbon::now();
			$invoice_detail->receipt_id = $receipt_id;

			$job_order->invoice()->save($invoice_detail);

			// dump($job_order->invoice);
			if($job_order->status_id == 8478)
			{
				$gate_log = GateLog::where('job_order_id', $job_order->id)->first();
				// dd($gate_log);

				if ($gate_log) {

					if (date('m') > 3) {
						$year = date('Y') + 1;
					} else {
						$year = date('Y');
					}
					//GET FINANCIAL YEAR ID
					$financial_year = FinancialYear::where('from', $year)
						->where('company_id', $gate_log->company_id)
						->first();

					$branch = Outlet::where('id', $gate_log->outlet_id)->first();

					if ($branch && $financial_year) {

						//GENERATE GatePASS
						$generateNumber = SerialNumberGroup::generateNumber(29, $financial_year->id, $branch->state_id, $branch->id);

						if ($generateNumber['success']) {
							$gate_pass = GatePass::firstOrNew(['job_order_id' => $job_order->id, 'type_id' => 8280]); //VEHICLE GATE PASS

							$gate_pass->gate_pass_of_id = 11280;
							$gate_pass->entity_id = $job_order->id;

							if (!$gate_pass->exists) {
								$gate_pass->updated_at = Carbon::now();
								$gate_pass->updated_by_id = Auth::user()->id;
							} else {
								$gate_pass->created_at = Carbon::now();
								$gate_pass->created_by_id = Auth::user()->id;
							}

							$gate_pass->company_id = $gate_log->company_id;
							$gate_pass->number = $generateNumber['number'];
							$gate_pass->status_id = 8340; //GATE OUT PENDING
							$gate_pass->save();

							$gate_log->gate_pass_id = $gate_pass->id;
							$gate_log->status_id = 8123; //GATE OUT PENDING
							$gate_log->updated_by_id = Auth::user()->id;
							$gate_log->updated_at = Carbon::now();
							$gate_log->save();
						}

						//Generate GatePass PDF
						$generate_estimate_gatepass_pdf = JobOrder::generateEstimateGatePassPDF($job_order->id, $type = 'GateIn');
						// $generate_covering_pdf = JobOrder::generateCoveringLetterPDF($job_order->id);
					}
				}
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => $message,
			]);
			


			// if($request->form_type == 2)
			// {
			// 	$gate_pass = GatePass::find($request->gate_pass_id);

			// 	if(!$gate_pass)
			// 	{
			// 		return response()->json([
			// 			'success' => false,
			// 			'error' => 'Validation Error',
			// 			'errors' => ['Gate Pass Not Found!'],
			// 		]);
			// 	}

			// 	DB::beginTransaction();

			// 	if($gate_pass->status_id == 11400)
			// 	{
			// 		if($gate_pass->type_id == 8282)
			// 		{
			// 			$gate_pass->status_id = 11402;
			// 		}
			// 		else
			// 		{
			// 			$gate_pass->status_id = 11401;
			// 		}

			// 		$gate_pass->gate_out_date = Carbon::now();
			// 	}
			// 	elseif($gate_pass->status_id == 11402)
			// 	{
			// 		$gate_pass->status_id = 11403;
			// 		$gate_pass->gate_in_date = Carbon::now();
			// 	}
			// 	elseif($gate_pass->status_id == 11403)
			// 	{
			// 		$gate_pass->status_id = 11404;
			// 	}

			// 	$gate_pass->save();

			// 	//Update parts Details
			// 	if (($gate_pass->status_id != 11401 && $gate_pass->status_id != 11402 ) && isset($request->invoice_items)) {
			// 		foreach ($request->invoice_items as $key => $invoice_item) {
						
			// 			$gate_pass_item = GatePassInvoiceItem::firstOrNew([
			// 				'id' => $invoice_item['item_id'],
			// 			]);

			// 			if(isset($invoice_item['returned_qty']))
			// 			{
			// 				$gate_pass_item->returned_qty =  $invoice_item['returned_qty'];
			// 				$gate_pass_item->status_id = 11421;
			// 			}
			// 			else
			// 			{
			// 				$gate_pass_item->returned_qty =  NULL;
			// 				$gate_pass_item->status_id = 11420;
			// 			}
			// 			$gate_pass_item->save();

			// 		}	
			// 	}

			// 	//Update Gate Out date time 
			// 	if ($gate_pass->status_id == 11401 || $gate_pass->status_id == 11402 ) 
			// 	{
			// 		$gate_pass_user = GatePassUser::where('gate_pass_id',$gate_pass->id)->update(['gate_out_date_time'=> Carbon::now(),'updated_by_id'=>Auth::user()->id,'updated_at'=> Carbon::now()]);
			// 	}

			// 	//Update Gate In date time 
			// 	if ($gate_pass->status_id == 11403) 
			// 	{
			// 		$gate_pass_user = GatePassUser::where('gate_pass_id',$gate_pass->id)->update(['gate_in_date_time'=> Carbon::now(),'updated_by_id'=>Auth::user()->id,'updated_at'=> Carbon::now()]);
			// 	}

			// 	DB::commit();

			// 	return response()->json([
			// 		'success' => true,
			// 		'message' => 'GatePass Updated successfully!!',
			// 	]);
			// }
			// else
			// {
			// 	$validator = Validator::make($request->all(), [
			// 		'type' => [
			// 			'required',
			// 		],
			// 		'purpose_id' => [
			// 			'required',
			// 			'integer',
			// 			'exists:configs,id',
			// 		],
			// 		// 'other_purpose' => [
			// 		// 	'required',
			// 		// ],
			// 		'hand_over_to' => [
			// 			'required',
			// 		],
			// 	]);
		
			// 	if ($validator->fails()) {
			// 		return response()->json([
			// 			'success' => false,
			// 			'error' => 'Validation Error',
			// 			'errors' => $validator->errors()->all(),
			// 		]);
			// 	}
		
			// 	DB::beginTransaction();
				
			// 	//remove items
			// 	$removal_item_ids = json_decode($request->removal_item_ids);
			// 	if (!empty($removal_item_ids)) {
			// 		$invoice_items = GatePassInvoiceItem::whereIn('id', $removal_item_ids)->forceDelete();
			// 	}

			// 	//remove users
			// 	$removal_user_ids = json_decode($request->removal_user_ids);
			// 	if (!empty($removal_user_ids)) {
			// 		$users = GatePassUser::whereIn('id', $removal_user_ids)->forceDelete();
			// 	}
		
			// 	$gate_pass = GatePass::firstOrNew(['id'=>$request->id]);
			// 	if($request->type == 'Returnable')
			// 	{
			// 		$gate_pass->type_id = 8282;
			// 		$gate_pass->job_card_id = $request->job_card_id;
			// 	}
			// 	else
			// 	{
			// 		$gate_pass->type_id = 8283;
			// 		$gate_pass->job_card_id = NULL;
			// 	}
		
			// 	if (!$gate_pass->exists) {
			// 		if (date('m') > 3) {
			// 			$year = date('Y') + 1;
			// 		} else {
			// 			$year = date('Y');
			// 		}
			// 		//GET FINANCIAL YEAR ID
			// 		$financial_year = FinancialYear::where('from', $year)
			// 			->where('company_id', Auth::user()->company_id)
			// 			->first();
			// 		if (!$financial_year) {
			// 			return response()->json([
			// 				'success' => false,
			// 				'error' => 'Validation Error',
			// 				'errors' => [
			// 					'Fiancial Year Not Found',
			// 				],
			// 			]);
			// 		}
			// 		//GET BRANCH/OUTLET
			// 		$branch = Outlet::where('id', Auth::user()->working_outlet_id)->first();
		
			// 		$generateNumber = SerialNumberGroup::generateNumber(139, $financial_year->id, $branch->state_id, $branch->id);
			// 			if (!$generateNumber['success']) {
			// 				return response()->json([
			// 					'success' => false,
			// 					'error' => 'Validation Error',
			// 					'errors' => [
			// 						'No Serial number found for FY : ' . $financial_year->from . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
			// 					],
			// 				]);
			// 			}
		
			// 			$error_messages_2 = [
			// 				'number.required' => 'Serial number is required',
			// 				'number.unique' => 'Serial number is already taken',
			// 			];
		
			// 			$validator_2 = Validator::make($generateNumber, [
			// 				'number' => [
			// 					'required',
			// 					'unique:gate_passes,number,' . $gate_pass->id . ',id,company_id,' . Auth::user()->company_id,
			// 				],
			// 			], $error_messages_2);
		
			// 			if ($validator_2->fails()) {
			// 				return response()->json([
			// 					'success' => false,
			// 					'error' => 'Validation Error',
			// 					'errors' => $validator_2->errors()->all(),
			// 				]);
			// 			}
			// 		$gate_pass->number = $generateNumber['number'];
			// 	}
		
			// 	$gate_pass->job_card_id = $request->job_card_id;
			// 	$gate_pass->purpose_id = $request->purpose_id;
			// 	$gate_pass->other_remarks = $request->other_purpose;
			// 	$gate_pass->hand_over_to = $request->hand_over_to;
			// 	$gate_pass->status_id = 11400;
			// 	$gate_pass->company_id = Auth::user()->company_id;
			// 	$gate_pass->outlet_id = Auth::user()->working_outlet_id;
			// 	$gate_pass->save();
		
			// 	if (isset($request->part_details)) {
			// 		foreach ($request->part_details as $key => $part_detail) {
			// 			$gate_pass_item = GatePassInvoiceItem::firstOrNew([
			// 				'id' => $part_detail['id'],
			// 			]);
		
			// 			if($gate_pass_item->exists)
			// 			{
			// 				$gate_pass_item->updated_by_id = Auth::user()->id;
			// 				$gate_pass_item->updated_at = Carbon::now();
			// 			}
			// 			else
			// 			{
			// 				$gate_pass_item->created_by_id = Auth::user()->id;
			// 				$gate_pass_item->created_at = Carbon::now();
			// 			}
						
			// 			$gate_pass_item->gate_pass_id = $gate_pass->id;
			// 			$gate_pass_item->category_id =  $part_detail['category_id'];
			// 			$gate_pass_item->entity_id =  isset($part_detail['entity_id']) ? $part_detail['entity_id'] : NULL;
			// 			$gate_pass_item->entity_name = isset($part_detail['entity_name']) ? $part_detail['entity_name'] : NULL;
			// 			$gate_pass_item->entity_description =  $part_detail['entity_description'];
			// 			$gate_pass_item->issue_qty =  $part_detail['issue_qty'];
			// 			$gate_pass_item->status_id = 11420;
			// 			$gate_pass_item->save();
			// 		}	
			// 	}else
			// 	{
			// 		return response()->json([
			// 			'success' => false,
			// 			'error' => 'Validation Error',
			// 			'errors' => ['Kindly add atleast one part or tool!'],
			// 		]);
			// 	}
				
			// 	if($request->type == 'Non Returnable')
			// 	{
			// 		$gate_pass_invoice = GatePassInvoice::firstOrNew(['gate_pass_id'=>$gate_pass->id]);
		
			// 		if (!$gate_pass_invoice->exists) {
			// 			$gate_pass_invoice->created_at = Carbon::now();
			// 			$gate_pass_invoice->created_by_id = Auth::user()->id;
			// 		}
			// 		else
			// 		{
			// 			$gate_pass_invoice->updated_at = Carbon::now();
			// 			$gate_pass_invoice->updated_by_id = Auth::user()->id;
			// 		}
		
			// 		$gate_pass_invoice->invoice_number = $request->invoice_number;
			// 		$gate_pass_invoice->invoice_date = date('Y-m-d', strtotime($request->invoice_date));
			// 		$gate_pass_invoice->invoice_amount = $request->invoice_amount;
			// 		$gate_pass_invoice->save();
			// 	}
			// 	else
			// 	{
			// 		$gate_pass_invoice = GatePassInvoice::where('gate_pass_id', $gate_pass->id)->forceDelete();
			// 	}
		
			// 	//Customer
			// 	$gate_pass_customer = GatePassCustomer::firstOrNew(['gate_pass_id'=>$gate_pass->id]);
		
			// 	if (!$gate_pass_customer->exists) {
			// 		$gate_pass_customer->created_at = Carbon::now();
			// 		$gate_pass_customer->created_by_id = Auth::user()->id;
			// 	}
			// 	else
			// 	{
			// 		$gate_pass_customer->updated_at = Carbon::now();
			// 		$gate_pass_customer->updated_by_id = Auth::user()->id;
			// 	}
		
			// 	$gate_pass_customer->customer_name = $request->customer_name;
			// 	$gate_pass_customer->customer_address = $request->customer_address;
			// 	$gate_pass_customer->customer_id = $request->customer_id;
		
			// 	$gate_pass_customer->save();
				
			// 	//User Save
			// 	if (isset($request->user_details)) {
			// 		foreach ($request->user_details as $key => $user_detail) {
			// 			if(isset($user_detail['user_id']))
			// 			{
			// 				$gate_pass_user = GatePassUser::firstOrNew([
			// 					'id' => $user_detail['id'],
			// 				]);
			
			// 				if($gate_pass_user->exists)
			// 				{
			// 					$gate_pass_user->updated_by_id = Auth::user()->id;
			// 					$gate_pass_user->updated_at = Carbon::now();
			// 				}
			// 				else
			// 				{
			// 					$gate_pass_user->created_by_id = Auth::user()->id;
			// 					$gate_pass_user->created_at = Carbon::now();
			// 				}
							
			// 				$gate_pass_user->gate_pass_id = $gate_pass->id;
			// 				$gate_pass_user->user_id =  $user_detail['user_id'];
			// 				$gate_pass_user->save();
			// 			}
			// 		}	
			// 	}
				
			// 	if($gate_pass->type_id == 8283 || $request->purpose_id !=11360)
			// 	{
			// 		$users = GatePassUser::where('gate_pass_id', $gate_pass->id)->forceDelete();
			// 	}
			
			// 	DB::commit();
		
			// 	return response()->json([
			// 		'success' => true,
			// 		'message' => 'GatePass Saved successfully!!',
			// 	]);
			// }
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
    }
}
