<?php

namespace Abs\GigoPkg\Api;

use Abs\GigoPkg\AmcMember;
use Abs\SerialNumberPkg\SerialNumberGroup;
use App\Attachment;
use App\Config;
use App\Customer;
use App\Employee;
use App\FinancialYear;
use App\GateLog;
use App\GatePass;
use App\GigoManualInvoice;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WpoSoapController;
use App\JobOrder;
use App\OnSiteOrderPart;
use App\OnSiteOrderEstimate;
use App\JobOrderWarrantyDetail;
use App\JobOrderPaymentDetail;
use App\MailConfiguration;
use App\Mail\VehicleDeliveryRequestMail;
use App\Outlet;
use App\OnSiteOrderRepairOrder;
use App\OnSiteOrder;
use App\Payment;
use App\PaymentMode;
use App\PendingReason;
use App\Receipt;
use App\User;
use App\Country;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Mail;
use Storage;
use Validator;

class OnSiteVisitController extends Controller
{
    public $successStatus = 200;

    public function __construct(WpoSoapController $getSoap = null)
    {
        $this->getSoap = $getSoap;
    }

    public function getGateInList(Request $request)
    {
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

    public function getLabourPartsData($params) {

		$result = array();

		$site_visit = OnSiteOrder::with([
            'company',
            'outlet',
            'onSiteVisitUser',
            'customer',
            'outlet',
            'status',
            'onSiteOrderRepairOrders',
            'onSiteOrderParts',
        ])->where('id', $params['on_site_order_id'])->first();

		$customer_paid_type = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		$labour_amount = 0;
		$part_amount = 0;

		$labour_details = array();
		$labours = array();

		if ($site_visit->onSiteOrderRepairOrders) {
			foreach ($site_visit->onSiteOrderRepairOrders as $key => $value) {
				$labour_details[$key]['id'] = $value->id;
				$labour_details[$key]['labour_id'] = $value->repair_order_id;
				$labour_details[$key]['code'] = $value->repairOrder->code;
				$labour_details[$key]['name'] = $value->repairOrder->name;
				$labour_details[$key]['type'] = $value->repairOrder->repairOrderType ? $value->repairOrder->repairOrderType->short_name : '-';
				$labour_details[$key]['qty'] = $value->qty;
				$repair_order = $value->repairOrder;
				if ($value->repairOrder->is_editable == 1) {
					$labour_details[$key]['rate'] = $value->amount;
					$repair_order->amount = $value->amount;
				} else {
					$labour_details[$key]['rate'] = $value->repairOrder->amount;
				}

				$labour_details[$key]['amount'] = $value->amount;
				$labour_details[$key]['split_order_type'] = $value->splitOrderType ? $value->splitOrderType->code . "|" . $value->splitOrderType->name : '-';
				$labour_details[$key]['removal_reason_id'] = $value->removal_reason_id;
				$labour_details[$key]['split_order_type_id'] = $value->split_order_type_id;
				$labour_details[$key]['repair_order'] = $repair_order;
				$labour_details[$key]['customer_voice'] = $value->customerVoice;
				$labour_details[$key]['customer_voice_id'] = $value->customer_voice_id;
				$labour_details[$key]['status_id'] = $value->status_id;
				if (in_array($value->split_order_type_id, $customer_paid_type) || !$value->split_order_type_id) {
					if ($value->is_free_service != 1 && $value->removal_reason_id == null) {
						$labour_amount += $value->amount;
					} else {
						$labour_details[$key]['amount'] = 0;
					}
				} else {
					$labour_details[$key]['amount'] = 0;
				}

				$labours[$key]['id'] = $value->repair_order_id;
				$labours[$key]['code'] = $value->repairOrder->code;
				$labours[$key]['name'] = $value->repairOrder->name;
			}
		}

		$part_details = array();
		if ($site_visit->onSiteOrderParts) {
			foreach ($site_visit->onSiteOrderParts as $key => $value) {
				$part_details[$key]['id'] = $value->id;
				$part_details[$key]['part_id'] = $value->part_id;
				$part_details[$key]['code'] = $value->part->code;
				$part_details[$key]['name'] = $value->part->name;
				$part_details[$key]['type'] = $value->part->partType ? $value->part->partType->name : '-';
				$part_details[$key]['rate'] = $value->rate;
				$part_details[$key]['qty'] = $value->qty;
				$part_details[$key]['amount'] = $value->amount;
				$part_details[$key]['split_order_type'] = $value->splitOrderType ? $value->splitOrderType->code . "|" . $value->splitOrderType->name : '-';
				$part_details[$key]['removal_reason_id'] = $value->removal_reason_id;
				$part_details[$key]['split_order_type_id'] = $value->split_order_type_id;
				$part_details[$key]['part'] = $value->part;
				$part_details[$key]['status_id'] = $value->status_id;
				$part_details[$key]['customer_voice'] = $value->customerVoice;
				$part_details[$key]['customer_voice_id'] = $value->customer_voice_id;
				$part_details[$key]['repair_order'] = $value->part->repair_order_parts;

				if (in_array($value->split_order_type_id, $customer_paid_type) || !$value->split_order_type_id) {
					if ($value->is_free_service != 1 && $value->removal_reason_id == null) {
						$part_amount += $value->amount;
					} else {
						$part_details[$key]['amount'] = 0;
					}
				} else {
					$part_details[$key]['amount'] = 0;
				}
			}
		}

		$total_amount = $part_amount + $labour_amount;

		$result['labour_details'] = $labour_details;
		$result['part_details'] = $part_details;
		$result['labour_amount'] = $labour_amount;
		$result['part_amount'] = $part_amount;
		$result['total_amount'] = $total_amount;
		// $result['labours'] = $labours;

		return $result;
	}

    public function getFormData(Request $request)
    {
        // dd($request->all());
        if($request->id){
            $site_visit = OnSiteOrder::find($request->id);

            if (!$site_visit) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Site Visit Detail Not Found!',
                    ],
                ]);
            }

            $params['on_site_order_id'] = $request->id;

            $result = $this->getLabourPartsData($params);
        }else{
            $site_visit = ''; 
            $result['part_details'] = [];
            $result['labour_details'] = [];  
            $result['total_amount'] = 0;  
            $result['labour_amount'] = 0;  
            $result['part_amount'] = 0;  
            $result['labours'] = [];  
        }

        $this->data['success'] = true;
        
        $extras = [
            'country_list' => Country::getDropDownList(),
			'state_list' => [], //State::getDropDownList(),
			'city_list' => [], //City::getDropDownList(),
        ];

        // $this->data['site_visit'] = $site_visit;
        $this->data['extras'] = $extras;
        
        return response()->json([
            'success' => true,
            'site_visit' => $site_visit,
            'part_details' => $result['part_details'],
            'labour_details' => $result['labour_details'],
            'total_amount' => $result['total_amount'],
            'labour_amount' => $result['labour_amount'],
            'parts_rate' => $result['part_amount'],
            'labours' => $result['labours'],
            'extras' => $extras,
        ]);
    }

    public function saveLabourDetail(Request $request) {
		dd($request->all());
		try {
			$error_messages = [
				'rot_id.unique' => 'Labour is already taken',
			];

			$validator = Validator::make($request->all(), [
				'on_site_order_id' => [
					'required',
					'integer',
					'exists:on_site_orders,id',
				],
				'rot_id' => [
					'required',
					'integer',
					'exists:repair_orders,id',
					'unique:on_site_order_repair_orders,repair_order_id,' . $request->on_site_repair_order_id . ',id,on_site_order_id,' . $request->on_site_order_id,
				],
				'split_order_type_id' => [
					'required',
					'integer',
					'exists:split_order_types,id',
				],
			], $error_messages);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			//Estimate Order ID
			$on_site_order = OnSiteOrder::find($request->on_site_order_id);

			$customer_paid_type = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

			if (!$on_site_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'On Site Visit Not Found!',
					],
				]);
			}

			DB::beginTransaction();

				$on_site_order->is_customer_approved = 0;
				// $job_order->status_id = 8463;
				$on_site_order->save();

			$estimate_id = OnSiteOrderEstimate::where('on_site_order_id', $job_order->id)->where('status_id', 10071)->first();
			if ($estimate_id) {
				$estimate_order_id = $estimate_id->id;
			} else {
				if (date('m') > 3) {
					$year = date('Y') + 1;
				} else {
					$year = date('Y');
				}
				//GET FINANCIAL YEAR ID
				$financial_year = FinancialYear::where('from', $year)
					->where('company_id', Auth::user()->company_id)
					->first();
				if (!$financial_year) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Fiancial Year Not Found',
						],
					]);
				}
				//GET BRANCH/OUTLET
				$branch = Outlet::where('id', $on_site_order->outlet_id)->first();

				//GENERATE GATE IN VEHICLE NUMBER
				$generateNumber = SerialNumberGroup::generateNumber(151, $financial_year->id, $branch->state_id, $branch->id);
				if (!$generateNumber['success']) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'No Estimate Reference number found for FY : ' . $financial_year->year . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
						],
					]);
				}

				$estimate = new JobOrderEstimate;
				$estimate->on_site_order_id = $on_site_order->id;
				$estimate->number = $generateNumber['number'];
				$estimate->status_id = 10071;
				$estimate->created_by_id = Auth::user()->id;
				$estimate->created_at = Carbon::now();
				$estimate->save();

				$estimate_order_id = $estimate->id;
			}

			$repair_order = RepairOrder::find($request->rot_id);
            if (!$repair_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Repair order / Labour Not Found!',
					],
				]);
			}            

			if (!empty($request->on_site_repair_order_id)) {
				$on_site_repair_order = OnSiteOrderRepairOrder::find($request->on_site_repair_order_id);
                $on_site_repair_order->updated_by_id = Auth::user()->id;
                $on_site_repair_order->updated_at = Carbon::now();
			} else {
				$on_site_repair_order = new OnSiteOrderRepairOrder;
                $on_site_repair_order->created_by_id = Auth::user()->id;
                $on_site_repair_order->created_at = Carbon::now();
			}

			$on_site_repair_order->on_site_order_id = $request->on_site_order_id;
			$on_site_repair_order->repair_order_id = $request->rot_id;
			$on_site_repair_order->qty = $repair_order->hours;
			$on_site_repair_order->split_order_type_id = $request->split_order_type_id;
			$on_site_repair_order->estimate_order_id = $estimate_order_id;
			
			if (in_array($request->split_order_type_id, $customer_paid_type)) {
				$on_site_repair_order->status_id = 8180; //Customer Approval Pending
				$on_site_repair_order->is_customer_approved = 0;
			} else {
				$on_site_repair_order->is_customer_approved = 1;
				$on_site_repair_order->status_id = 8181; //Mechanic Not Assigned
			}
			
			$on_site_repair_order->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Repair order detail saved successfully!!',
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

    public function savePartsDetail(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'on_site_order_id' => [
					'required',
					'integer',
					'exists:on_site_orders,id',
				],
				'part_id' => [
					'required',
					'integer',
					'exists:parts,id',
				],

				/*'split_order_id' => [
					'required',
					'integer',
					'exists:split_order_types,id',
				],*/
				'qty' => [
					'required',
					'numeric',
				],

			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			//Estimate Order ID
			$on_site_order = OnSiteOrder::find($request->on_site_order_id);

			$customer_paid_type = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

			if (!$on_site_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'On Site Visit Not Found!',
					],
				]);
			}

			DB::beginTransaction();

			$on_site_order->is_customer_approved = 0;
			// $on_site_visit->status_id = 8463;
			$on_site_order->save();

			$estimate_id = OnSiteOrderEstimate::where('on_site_order_id', $job_order->id)->where('status_id', 10071)->first();
			if ($estimate_id) {
				$estimate_order_id = $estimate_id->id;
			} else {
				if (date('m') > 3) {
					$year = date('Y') + 1;
				} else {
					$year = date('Y');
				}
				//GET FINANCIAL YEAR ID
				$financial_year = FinancialYear::where('from', $year)
					->where('company_id', Auth::user()->company_id)
					->first();
				if (!$financial_year) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Fiancial Year Not Found',
						],
					]);
				}
				//GET BRANCH/OUTLET
				$branch = Outlet::where('id', $on_site_order->outlet_id)->first();

				//GENERATE GATE IN VEHICLE NUMBER
				$generateNumber = SerialNumberGroup::generateNumber(151, $financial_year->id, $branch->state_id, $branch->id);
				if (!$generateNumber['success']) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'No Estimate Reference number found for FY : ' . $financial_year->year . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
						],
					]);
				}

				$estimate = new JobOrderEstimate;
				$estimate->on_site_order_id = $on_site_order->id;
				$estimate->number = $generateNumber['number'];
				$estimate->status_id = 10071;
				$estimate->created_by_id = Auth::user()->id;
				$estimate->created_at = Carbon::now();
				$estimate->save();

				$estimate_order_id = $estimate->id;
			}

			$customer_paid_type = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

			$part = Part::with(['partStock'])->where('id', $request->part_id)->first();
            if (!$part) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Part Not Found',
                    ],
                ]);
            }

			$request_qty = $request->qty;

			if (!empty($request->on_site_part_id)) {
				$on_site_part = OnSiteOrderPart::find($request->on_site_part_id);
				$on_site_part->updated_by_id = Auth::user()->id;
				$on_site_part->updated_at = Carbon::now();
			} else {
				//Check Request parts are already requested or not.
				$on_site_part = OnSiteOrderPart::where('on_site_order_id', $request->on_site_order_id)->where('part_id', $request->part_id)->where('status_id', 8200)->where('is_customer_approved', 0)->whereNull('removal_reason_id')->first();
				if ($on_site_part) {
					$request_qty = $on_site_part->qty + $request->qty;
					$on_site_part->updated_by_id = Auth::user()->id;
					$on_site_part->updated_at = Carbon::now();
				} else {
					$on_site_part = new OnSiteOrderPart;
					$on_site_part->created_by_id = Auth::user()->id;
					$on_site_part->created_at = Carbon::now();
				}
				$on_site_part->estimate_order_id = $estimate_order_id;
			}

			$part_mrp = $request->mrp ? $request->mrp : 0;
			$on_site_part->on_site_order_id = $request->on_site_order_id;
			$on_site_part->part_id = $request->part_id;

			$on_site_part->rate = $part_mrp;
			$on_site_part->qty = $request_qty;
			$on_site_part->split_order_type_id = $request->split_order_type_id;
			$on_site_part->amount = $request_qty * $part_mrp;

			if (!$request->split_order_type_id || in_array($request->split_order_type_id, $customer_paid_type)) {
				$on_site_part->status_id = 8200; //Customer Approval Pending
				$on_site_part->is_customer_approved = 0;
			} else {
				$on_site_part->is_customer_approved = 1;
				$on_site_part->status_id = 8201; //Not Issued
			}

			$on_site_part->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Part detail saved Successfully!!',
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

    public function updateVehicleStatus(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'job_order_id' => [
                'required',
                'integer',
                'exists:job_orders,id',
            ],
            'vehicle_delivery_status_id' => [
                'required',
                'integer',
                'exists:vehicle_delivery_statuses,id',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation Error',
                'errors' => $validator->errors()->all(),
            ]);
        }

        $job_order = JobOrder::with('gateLog')->find($request->job_order_id);

        if (!$job_order) {
            return response()->json([
                'success' => false,
                'error' => 'Validation Error',
                'errors' => [
                    'Job Order Not Found!',
                ],
            ]);
        }

        if (!$job_order->gateLog) {
            return response()->json([
                'success' => false,
                'error' => 'Validation Error',
                'errors' => [
                    'Gate Log Not Found!',
                ],
            ]);
        }

        DB::beginTransaction();

        if ($job_order->gateLog->status_id == 8124) {
            $job_order->vehicle_delivery_status_id = 3;
        } else {
            $job_order->vehicle_delivery_status_id = $request->vehicle_delivery_status_id;
        }

        $job_order->updated_by_id = Auth::user()->id;
        $job_order->updated_at = Carbon::now();
        $job_order->save();

        DB::commit();
        $message = "Vehicle Status Updated successfully!";

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
    public function save(Request $request)
    {
        // dd($request->all());
        try {

            if ($request->type_id == 1) {

                if ($request->vehicle_service_status == 1) {
                    if ($request->billing_type_id == 11520) {
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
                            //     'required',
                            // ],
                            'invoice_date' => [
                                'required',
                            ],
                            // 'invoice_amount' => [
                            //     'required',
                            // ],
                            'labour_invoice_number' => [
                                'required',
                                'unique:gigo_manual_invoices,number,' . $request->job_order_id . ',invoiceable_id,invoice_type_id,1',
                            ],
                            'labour_amount' => [
                                'required',
                            ],
                            'parts_invoice_number' => [
                                'required',
                                'unique:gigo_manual_invoices,number,' . $request->job_order_id . ',invoiceable_id,invoice_type_id,2',
                            ],
                            'parts_amount' => [
                                'required',
                            ],
                            // 'receipt_number' => [
                            //     'required_if:vehicle_payment_status,==,1',
                            // ],
                            // 'receipt_date' => [
                            //     'required_if:vehicle_payment_status,==,1',
                            // ],
                            // 'receipt_amount' => [
                            //     'required_if:vehicle_payment_status,==,1',
                            // ],
                            'vehicle_delivery_request_remarks' => [
                                'required_if:vehicle_payment_status,==,0',
                            ],
                            'job_card_number' => [
                                'required',
                            ],
                        ], $error_messages);

                        if ($validator->fails()) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => $validator->errors()->all(),
                            ]);
                        }

                        // if ($request->vehicle_payment_status == 1) {
                        //     // $validator = Validator::make($request->all(), [
                        //     //     'receipt_number' => [
                        //     //         'required',
                        //     //         // 'unique:receipts,temporary_receipt_no,' . $request->job_order_id . ',entity_id,receipt_of_id,7622',
                        //     //         // 'unique:receipts,permanent_receipt_no,' . $request->job_order_id . ',entity_id,receipt_of_id,7622',
                        //     //         'unique:job_order_payment_details,transaction_number,' . $request->job_order_id . ',job_order_id',
                        //     //     ],
                        //     // ]);

                        //     // if ($validator->fails()) {
                        //     //     return response()->json([
                        //     //         'success' => false,
                        //     //         'error' => 'Validation Error',
                        //     //         'errors' => $validator->errors()->all(),
                        //     //     ]);
                        //     // }

                        //     // if (strtotime($request->invoice_date) > strtotime($request->receipt_date)) {
                        //     //     return response()->json([
                        //     //         'success' => false,
                        //     //         'error' => 'Validation Error',
                        //     //         'errors' => [
                        //     //             'Receipt Date should be greater than Invoice Date',
                        //     //         ],
                        //     //     ]);
                        //     // }
                        // }

                        $job_order = JobOrder::with('gateLog')->find($request->job_order_id);

                        if (!$job_order) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => [
                                    'Job Order Not Found!',
                                ],
                            ]);
                        }

                        if (!$job_order->customer_id) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => [
                                    'Customer Not Found!',
                                ],
                            ]);
                        }

                        $gate_in_date = $job_order->gateLog->gate_in_date;
                        $gate_in_date = date('d-m-Y', strtotime($gate_in_date));

                        if (strtotime($gate_in_date) > strtotime($request->invoice_date)) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => [
                                    'Invoice Date should be greater than Gate In Date',
                                ],
                            ]);
                        }

                        DB::beginTransaction();

                        //Check Invoice,Receipt amount
                        $labour_amount = $request->labour_amount;
                        $parts_amount = $request->parts_amount;
                        // $receipt_amount = $request->receipt_amount ? $request->receipt_amount : 0;
                        $receipt_amount = 0;
                        $payment_status = 0;
                        $status_id = 8477;

                        //Check Paid Amount 
                        if($request->payment){
                            foreach($request->payment as $payment){
                                // if (strtotime($gate_in_date) > strtotime($payment['receipt_date'])) {
                                //     return response()->json([
                                //         'success' => false,
                                //         'error' => 'Validation Error',
                                //         'errors' => [
                                //             'Receipt Date should be greater than Gate In Date',
                                //         ],
                                //     ]);
                                // }
                                if($payment['receipt_amount'] > 0){
                                    $receipt_amount += $payment['receipt_amount'];
                                }
                            }
                        }
                        // dd($receipt_amount);
                        if ($receipt_amount) {
                            if ($receipt_amount == ($labour_amount + $parts_amount)) {
                                $payment_status = 1;
                                $status_id = 8468;
                            } else if ($receipt_amount > ($labour_amount + $parts_amount)) { 
                                return response()->json([
                                    'success' => false,
                                    'error' => 'Validation Error',
                                    'errors' => [
                                        'Paid Amount should be less than or equal to total bill amount',
                                    ],
                                ]);
                            } else {
                                $payment_status = 0;
                                $status_id = 8477;
                            }

                            //Check Reason
                            $pending_reason = PendingReason::find($request->pending_reason_id);
                            if ($pending_reason) {
                                if ($pending_reason->need_verification == 0) {
                                    $payment_status = 1;
                                    $status_id = 8467;
                                }
                            }
                        }

                        $job_order->jv_customer_id = null;
                        if ($request->pending_reason_id == 4) {
                            $job_order->jv_customer_id = $request->jv_customer_id;

                            if ($job_order->customer_id == $request->jv_customer_id) {
                                return response()->json([
                                    'success' => false,
                                    'error' => 'Validation Error',
                                    'errors' => [
                                        'JV Customer should be different from the Actual Customer!',
                                    ],
                                ]);
                            }
                        }

                        if ($payment_status) {
                            $job_order->pending_reason_id = $request->pending_reason_id ? $request->pending_reason_id : null;
                            $job_order->pending_remarks = $request->pending_remarks ? $request->pending_remarks : null;
                            $job_order->status_id = $status_id;

                            $message = "Vehicle delivery request saved successfully!";
                        } else {
                            $job_order->pending_reason_id = $request->pending_reason_id;
                            $job_order->pending_remarks = $request->pending_remarks;
                            $job_order->status_id = $status_id;

                            $message = "Vehicle delivery request sent to service head for successfully!";
                        }

                        $job_order->vehicle_payment_status = $request->vehicle_payment_status;
                        $job_order->vehicle_delivery_requester_id = Auth::user()->id;
                        $job_order->job_card_number = $request->job_card_number;

                        if ($request->vehicle_payment_status == 1) {
                            $job_order->vehicle_delivery_request_remarks = null;
                            // $job_order->status_id = 8468;
                            $payment_status_id = 2;
                        } else {
                            $job_order->vehicle_delivery_request_remarks = $request->vehicle_delivery_request_remarks;
                            // $job_order->status_id = 8477;
                            $payment_status_id = 1;
                        }

                        $job_order->inward_cancel_reason_id = null;
                        $job_order->inward_cancel_reason = null;
                        $job_order->billing_type_id = $request->billing_type_id;
                        $job_order->inward_cancel_reason_id = null;
                        $job_order->inward_cancel_reason = null;
                        $job_order->updated_by_id = Auth::user()->id;
                        $job_order->updated_at = Carbon::now();
                        $job_order->save();

                        //Delete previous receipt
                        // $remove_receipt = Receipt::where('receipt_of_id', 7622)->where('entity_id', $job_order->id)->forceDelete();

                        //Delete previous Invoice
                        $remove_invoice = GigoManualInvoice::where('invoiceable_type', 'App\JobOrder')->where('invoiceable_id', $job_order->id)->forceDelete();

                        $receipt_id = null;
                        if ($payment_status_id == 2) {

                            // $validator = Validator::make($request->all(), [
                            //     'receipt_number' => [
                            //         'required',
                            //         // 'unique:receipts,temporary_receipt_no,' . $request->job_order_id . ',entity_id,receipt_of_id,7622',
                            //         // 'unique:receipts,permanent_receipt_no,' . $request->job_order_id . ',entity_id,receipt_of_id,7622',
                            //         'unique:job_order_payment_details,transaction_number,' . $request->job_order_id . ',job_order_id',
                            //     ],
                            // ]);

                            // if ($validator->fails()) {
                            //     return response()->json([
                            //         'success' => false,
                            //         'error' => 'Validation Error',
                            //         'errors' => $validator->errors()->all(),
                            //     ]);
                            // }

                            $labour_amount = $request->labour_amount;
                            $parts_amount = $request->parts_amount;
                            // $receipt_amount = $request->receipt_amount;

                            // if($receipt_amount != ($labour_amount + $parts_amount))
                            // {
                            //     return response()->json([
                            //         'success' => false,
                            //         'error' => 'Validation Error',
                            //         'errors' => [
                            //             'Receipt amount should be equal to Invoice amount!',
                            //         ],
                            //     ]);
                            // }

                            //Save Receipt
                            $customer = Customer::find($job_order->customer_id);

                            // $receipt = new Receipt;
                            // $receipt->company_id = Auth::user()->company_id;
                            // $receipt->temporary_receipt_no = $request->receipt_number;
                            // $receipt->date = date('Y-m-d', strtotime($request->receipt_date));
                            // $receipt->outlet_id = $job_order->outlet_id;
                            // $receipt->receipt_of_id = 7622;
                            // $receipt->entity_id = $job_order->id;
                            // $receipt->permanent_receipt_no = $request->receipt_number;
                            // $receipt->amount = $request->receipt_amount;
                            // $receipt->settled_amount = $request->receipt_amount;
                            // $receipt->payment_mode_id = $request->payment_mode_id;
                            // $receipt->created_at = Carbon::now();

                            // $customer->receipt()->save($receipt);

                            // $receipt_id = $customer->receipt ? $customer->receipt[0] ? $customer->receipt[0]->id : null : null;

                            //Save Payment
                            $payment = new Payment;
                            // dd($payment);
                            $payment->entity_type_id = 8434;
                            $payment->entity_id = $job_order->id;
                            $payment->received_amount = $receipt_amount;
                            $payment->receipt_id = NULL;
                            $job_order->payment()->save($payment);

                            $remove_payment = JobOrderPaymentDetail::where('job_order_id', $job_order->id)->forceDelete();

                            //Check Paid Amount 
                            if($request->payment){
                                foreach($request->payment as $payment){

                                    //Check Receipt Number alreay saved or not
                                    $receipt_number = JobOrderPaymentDetail::where('transaction_number',$payment['receipt_number'])->first();

                                    if($receipt_number){
                                        return response()->json([
                                            'success' => false,
                                            'error' => 'Validation Error',
                                            'errors' => [
                                                'Receipt number has already been taken!',
                                            ],
                                        ]);
                                    }

                                     //save payment detail
                                    $job_order_payment = new JobOrderPaymentDetail;
                                    $job_order_payment->payment_mode_id =  $payment['payment_mode_id'];
                                    $job_order_payment->job_order_id = $job_order->id;
                                    $job_order_payment->transaction_number = $payment['receipt_number'];
                                    $job_order_payment->transaction_date = date('Y-m-d', strtotime($payment['receipt_date']));
                                    $job_order_payment->amount = $payment['receipt_amount'];
                                    $job_order_payment->save();
                                }
                            }
                           
                        }

                        //Save Labour Invoice Details
                        $invoice_detail = new GigoManualInvoice;
                        $invoice_detail->number = $request->labour_invoice_number;
                        $invoice_detail->invoice_type_id = 1;
                        $invoice_detail->outlet_id = $job_order->outlet_id;
                        $invoice_detail->customer_id = $job_order->customer_id;
                        $invoice_detail->amount = $request->labour_amount;
                        $invoice_detail->invoice_date = date('Y-m-d', strtotime($request->invoice_date));
                        $invoice_detail->payment_status_id = $payment_status_id;
                        $invoice_detail->created_by_id = Auth::user()->id;
                        $invoice_detail->created_at = Carbon::now();
                        $invoice_detail->receipt_id = NULL;

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
                        $invoice_detail->invoice_date = date('Y-m-d', strtotime($request->invoice_date));
                        $invoice_detail->payment_status_id = $payment_status_id;
                        $invoice_detail->created_by_id = Auth::user()->id;
                        $invoice_detail->created_at = Carbon::now();
                        $invoice_detail->receipt_id = NULL;

                        $job_order->invoice()->save($invoice_detail);

                        //CREATE DIRECTORY TO STORAGE PATH
                        $attachment_path = storage_path('app/public/gigo/job_order/attachments/');
                        Storage::makeDirectory($attachment_path, 0777);

                        //MULTIPLE ATTACHMENT REMOVAL
                        $attachment_removal_ids = json_decode($request->attachment_removal_ids);
                        if (!empty($attachment_removal_ids)) {
                            Attachment::whereIn('id', $attachment_removal_ids)->forceDelete();
                        }

                        if (!empty($request->transaction_attachments)) {
                            foreach ($request->transaction_attachments as $key => $transaction_attachment) {
                                $value = rand(1, 20);
                                $image = $transaction_attachment;

                                $file_name_with_extension = $image->getClientOriginalName();
                                $file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
                                $extension = $image->getClientOriginalExtension();
                                $name = $job_order->id . '_Transcation_Attachment_' . date('Y_m_d_h_i_s') . '_' . $value . '.' . $extension;

                                $transaction_attachment->move(storage_path('app/public/gigo/job_order/attachments/'), $name);
                                $attachement = new Attachment;
                                $attachement->attachment_of_id = 227; //Job order
                                $attachement->attachment_type_id = 11342; //GIGO Transcation Attachment
                                $attachement->entity_id = $job_order->id;
                                $attachement->name = $name;
                                $attachement->save();
                            }
                        }

                        // dump($job_order->invoice);
                        if ($payment_status) {
                            $gate_pass = $this->generateGatePass($job_order);
                        }

                        DB::commit();

                        //Send Mail for Serivice Head
                        if (!$payment_status) {
                            $this->vehiceRequestMail($job_order->id,$type = 1);
                        }

                    } elseif ($request->billing_type_id == 11523) {
                        $validator = Validator::make($request->all(), [
                            'job_order_id' => [
                                'required',
                                'integer',
                                'exists:job_orders,id',
                            ],
                            // 'invoice_number' => [
                            //     'required',
                            // ],
                            'invoice_date' => [
                                'required',
                            ],
                            // 'invoice_amount' => [
                            //     'required',
                            // ],
                            'labour_invoice_number' => [
                                'required',
                                'unique:gigo_manual_invoices,number,' . $request->job_order_id . ',invoiceable_id,invoice_type_id,1',
                            ],
                            'labour_amount' => [
                                'required',
                            ],
                            'parts_invoice_number' => [
                                'required',
                                'unique:gigo_manual_invoices,number,' . $request->job_order_id . ',invoiceable_id,invoice_type_id,2',
                            ],
                            'parts_amount' => [
                                'required',
                            ],
                            'job_card_number' => [
                                'required',
                            ],
                        ]);

                        if ($validator->fails()) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => $validator->errors()->all(),
                            ]);
                        }

                        $job_order = JobOrder::with('gateLog')->find($request->job_order_id);

                        if (!$job_order) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => [
                                    'Job Order Not Found!',
                                ],
                            ]);
                        }

                        if (!$job_order->customer_id) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => [
                                    'Customer Not Found!',
                                ],
                            ]);
                        }

                        $gate_in_date = $job_order->gateLog->gate_in_date;
                        $gate_in_date = date('d-m-Y', strtotime($gate_in_date));

                        if (strtotime($gate_in_date) > strtotime($request->invoice_date)) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => [
                                    'Invoice Date should be greater than Gate In Date',
                                ],
                            ]);
                        }

                        DB::beginTransaction();

                        $job_order->billing_type_id = $request->billing_type_id;
                        $job_order->inward_cancel_reason_id = null;
                        $job_order->inward_cancel_reason = null;
                        $job_order->vehicle_payment_status = null;
                        $job_order->pending_reason_id = null;
                        $job_order->jv_customer_id = null;
                        $job_order->pending_remarks = null;
                        $job_order->vehicle_delivery_requester_id = Auth::user()->id;
                        $job_order->vehicle_delivery_request_remarks = null;
                        $job_order->approver_id = null;
                        $job_order->approved_remarks = null;
                        $job_order->approved_date_time = null;
                        $job_order->warranty_reason = null;
                        $job_order->status_id = 8470;
                        $job_order->job_card_number = $request->job_card_number;
                        $job_order->updated_by_id = Auth::user()->id;
                        $job_order->updated_at = Carbon::now();
                        $job_order->save();

                        //Delete previous receipt
                        // $remove_receipt = Receipt::where('receipt_of_id', 7622)->where('entity_id', $job_order->id)->forceDelete();

                        //Delete previous Invoice
                        $remove_invoice = GigoManualInvoice::where('invoiceable_type', 'App\JobOrder')->where('invoiceable_id', $job_order->id)->forceDelete();

                        //Delete previous payment
                        $remove_payment = JobOrderPaymentDetail::where('job_order_id', $job_order->id)->forceDelete();

                        //Save Labour Invoice Details
                        $invoice_detail = new GigoManualInvoice;
                        $invoice_detail->number = $request->labour_invoice_number;
                        $invoice_detail->invoice_type_id = 1;
                        $invoice_detail->outlet_id = $job_order->outlet_id;
                        $invoice_detail->customer_id = $job_order->customer_id;
                        $invoice_detail->amount = $request->labour_amount;
                        $invoice_detail->invoice_date = date('Y-m-d', strtotime($request->invoice_date));
                        $invoice_detail->payment_status_id = 1;
                        $invoice_detail->created_by_id = Auth::user()->id;
                        $invoice_detail->created_at = Carbon::now();
                        $invoice_detail->receipt_id = null;

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
                        $invoice_detail->invoice_date = date('Y-m-d', strtotime($request->invoice_date));
                        $invoice_detail->payment_status_id = 1;
                        $invoice_detail->created_by_id = Auth::user()->id;
                        $invoice_detail->created_at = Carbon::now();
                        $invoice_detail->receipt_id = null;

                        $job_order->invoice()->save($invoice_detail);

                        //CREATE DIRECTORY TO STORAGE PATH
                        $attachment_path = storage_path('app/public/gigo/job_order/attachments/');
                        Storage::makeDirectory($attachment_path, 0777);

                        //MULTIPLE ATTACHMENT REMOVAL
                        $attachment_removal_ids = json_decode($request->attachment_removal_ids);
                        if (!empty($attachment_removal_ids)) {
                            Attachment::whereIn('id', $attachment_removal_ids)->forceDelete();
                        }

                        if (!empty($request->transaction_attachments)) {
                            foreach ($request->transaction_attachments as $key => $transaction_attachment) {
                                $value = rand(1, 20);
                                $image = $transaction_attachment;

                                $file_name_with_extension = $image->getClientOriginalName();
                                $file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
                                $extension = $image->getClientOriginalExtension();
                                $name = $job_order->id . '_Transcation_Attachment_' . date('Y_m_d_h_i_s') . '_' . $value . '.' . $extension;

                                $transaction_attachment->move(storage_path('app/public/gigo/job_order/attachments/'), $name);
                                $attachement = new Attachment;
                                $attachement->attachment_of_id = 227; //Job order
                                $attachement->attachment_type_id = 11342; //GIGO Transcation Attachment
                                $attachement->entity_id = $job_order->id;
                                $attachement->name = $name;
                                $attachement->save();
                            }
                        }

                        $gate_pass = $this->generateGatePass($job_order);

                        DB::commit();

                        $message = "Vehicle delivery request saved successfully!";

                    } else {
                        $validator = Validator::make($request->all(), [
                            'job_order_id' => [
                                'required',
                                'integer',
                                'exists:job_orders,id',
                            ],
                            'billing_type_id' => [
                                'required',
                                'integer',
                                'exists:configs,id',
                            ],
                            'warranty_reason' => [
                                'required',
                            ],
                            'job_card_number' => [
                                'required',
                            ],
                        ]);

                        if ($validator->fails()) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => $validator->errors()->all(),
                            ]);
                        }

                        if (empty($request->transaction_attachments) || count($request->transaction_attachments) == 0) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => [
                                    'Attachment Not Found!',
                                ],
                            ]);
                        }

                        $job_order = JobOrder::with('gateLog')->find($request->job_order_id);

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

                        if ($request->warranty_number) {

                            $validator = Validator::make($request->all(), [
                                'warranty_date' => [
                                    'required',
                                ],
                                'warranty_parts_amount' => [
                                    'required',
                                ],
                                'warranty_labour_amount' => [
                                    'required',
                                ],
                                'warranty_number' => [
                                    'unique:job_order_warranty_details,number,' . $request->job_order_id . ',job_order_id',
                                ],
                            ]);
    
                            if ($validator->fails()) {
                                return response()->json([
                                    'success' => false,
                                    'error' => 'Validation Error',
                                    'errors' => $validator->errors()->all(),
                                ]);
                            }

                            $gate_in_date = $job_order->gateLog->gate_in_date;
                            $gate_in_date = date('d-m-Y', strtotime($gate_in_date));

                            if (strtotime($gate_in_date) > strtotime($request->warranty_date)) {
                                return response()->json([
                                    'success' => false,
                                    'error' => 'Validation Error',
                                    'errors' => [
                                        'Warranty Date should be greater than Gate In Date',
                                    ],
                                ]);
                            }

                            //Save Warranty Detail
                            $warranty_detail = JobOrderWarrantyDetail::firstorNew(['job_order_id' => $job_order->id]);
                            $warranty_detail->number = $request->warranty_number;
                            $warranty_detail->labour_amount = $request->warranty_labour_amount;
                            $warranty_detail->parts_amount = $request->warranty_parts_amount;
                            $warranty_detail->warranty_date = date('Y-m-d', strtotime($request->warranty_date));
                            $warranty_detail->save();
                        }

                        $job_order->billing_type_id = $request->billing_type_id;
                        $job_order->job_card_number = $request->job_card_number;
                        $job_order->inward_cancel_reason_id = null;
                        $job_order->inward_cancel_reason = null;
                        $job_order->vehicle_payment_status = null;
                        $job_order->pending_reason_id = null;
                        $job_order->jv_customer_id = null;
                        $job_order->pending_remarks = null;
                        $job_order->vehicle_delivery_requester_id = Auth::user()->id;
                        $job_order->vehicle_delivery_request_remarks = null;
                        $job_order->approver_id = null;
                        $job_order->approved_remarks = null;
                        $job_order->approved_date_time = null;
                        $job_order->warranty_reason = $request->warranty_reason;
                        $job_order->status_id = 8470;
                        $job_order->updated_by_id = Auth::user()->id;
                        $job_order->updated_at = Carbon::now();
                        $job_order->save();

                        //CREATE DIRECTORY TO STORAGE PATH
                        $attachment_path = storage_path('app/public/gigo/job_order/attachments/');
                        Storage::makeDirectory($attachment_path, 0777);

                        //MULTIPLE ATTACHMENT REMOVAL
                        $attachment_removal_ids = json_decode($request->attachment_removal_ids);
                        if (!empty($attachment_removal_ids)) {
                            Attachment::whereIn('id', $attachment_removal_ids)->forceDelete();
                        }

                        if (!empty($request->transaction_attachments)) {
                            foreach ($request->transaction_attachments as $key => $transaction_attachment) {
                                $value = rand(1, 20);
                                $image = $transaction_attachment;

                                $file_name_with_extension = $image->getClientOriginalName();
                                $file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
                                $extension = $image->getClientOriginalExtension();
                                $name = $job_order->id . '_Transcation_Attachment_' . date('Y_m_d_h_i_s') . '_' . $value . '.' . $extension;

                                $transaction_attachment->move(storage_path('app/public/gigo/job_order/attachments/'), $name);
                                $attachement = new Attachment;
                                $attachement->attachment_of_id = 227; //Job order
                                $attachement->attachment_type_id = 11342; //GIGO Transcation Attachment
                                $attachement->entity_id = $job_order->id;
                                $attachement->name = $name;
                                $attachement->save();
                            }
                        }

                        $gate_pass = $this->generateGatePass($job_order);

                        //Delete previous receipt
                        // $remove_receipt = Receipt::where('receipt_of_id', 7622)->where('entity_id', $job_order->id)->forceDelete();

                        //Delete previous Invoice
                        $remove_invoice = GigoManualInvoice::where('invoiceable_type', 'App\JobOrder')->where('invoiceable_id', $job_order->id)->forceDelete();

                        //Delete previous payment
                        $remove_payment = JobOrderPaymentDetail::where('job_order_id', $job_order->id)->forceDelete();


                        DB::commit();
                        $message = "Vehicle delivery request saved successfully!";
                    }
                } else {
                    $validator = Validator::make($request->all(), [
                        'job_order_id' => [
                            'required',
                            'integer',
                            'exists:job_orders,id',
                        ],
                        'inward_cancel_reason_id' => [
                            'required',
                            'integer',
                            'exists:configs,id',
                        ],
                        'inward_cancel_reason' => [
                            'required',
                        ],
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => $validator->errors()->all(),
                        ]);
                    }

                    $job_order = JobOrder::with('gateLog')->find($request->job_order_id);

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

                    $job_order->status_id = 8470;

                    $job_order->billing_type_id = null;
                    $job_order->inward_cancel_reason_id = $request->inward_cancel_reason_id;
                    $job_order->inward_cancel_reason = $request->inward_cancel_reason;
                    $job_order->vehicle_payment_status = null;
                    $job_order->pending_reason_id = null;
                    $job_order->jv_customer_id = null;
                    $job_order->pending_remarks = null;
                    $job_order->vehicle_delivery_requester_id = Auth::user()->id;
                    $job_order->vehicle_delivery_request_remarks = null;
                    $job_order->approver_id = null;
                    $job_order->approved_remarks = null;
                    $job_order->approved_date_time = null;
                    $job_order->warranty_reason = null;

                    $job_order->updated_by_id = Auth::user()->id;
                    $job_order->updated_at = Carbon::now();
                    $job_order->save();

                    $gate_pass = $this->generateGatePass($job_order);

                    //Delete previous receipt
                    $remove_receipt = Receipt::where('receipt_of_id', 7622)->where('entity_id', $job_order->id)->forceDelete();

                    //Delete previous Invoice
                    $remove_invoice = GigoManualInvoice::where('invoiceable_type', 'App\JobOrder')->where('invoiceable_id', $job_order->id)->forceDelete();

                    DB::commit();
                    $message = "Vehicle delivery request saved successfully!";
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);

            } else if ($request->type_id == 2) {
                if($request->status == 'Reject'){
                    $error_messages = [
                        'rejected_remarks.required' => "Vehicle Delivery Reject Remarks is required",
                    ];
                    $validator = Validator::make($request->all(), [
                        'job_order_id' => [
                            'required',
                            'integer',
                            'exists:job_orders,id',
                        ],
                        'rejected_remarks' => [
                            'required',
                        ],
    
                    ], $error_messages);
                }else{
                    $error_messages = [
                        'approved_remarks.required' => "Vehicle Delivery Approval Remarks is required",
                    ];
                    $validator = Validator::make($request->all(), [
                        'job_order_id' => [
                            'required',
                            'integer',
                            'exists:job_orders,id',
                        ],
                        'approved_remarks' => [
                            'required',
                        ],
    
                    ], $error_messages);
                }
                
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

                $job_order->approver_id = Auth::user()->id;
                
                if($request->status == 'Reject'){
                    $job_order->status_id = 8479;
                    $job_order->rejected_remarks = $request->rejected_remarks;
                    $message = "Manual Vehicle Delivery Rejected Successfully!";
                }else{
                    $job_order->status_id = 8478;
                    $job_order->approved_remarks = $request->approved_remarks;
                    $job_order->approved_date_time = Carbon::now();
                    $message = "Manual Vehicle Delivery Approved Successfully!";
                }
                $job_order->save();

                if($request->status == 'Approve'){
                    $gate_pass = $this->generateGatePass($job_order);
                }

                DB::commit();

                //Send Approved Mail for user
                $this->vehiceRequestMail($job_order->id,$type = 2);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            } else {
                $validator = Validator::make($request->all(), [
                    'job_order_id' => [
                        'required',
                        'integer',
                        'exists:job_orders,id',
                    ],
                    'receipt_number' => [
                        'required',
                        // 'unique:receipts,temporary_receipt_no,' . $request->job_order_id . ',entity_id,receipt_of_id,7622',
                        // 'unique:receipts,permanent_receipt_no,' . $request->job_order_id . ',entity_id,receipt_of_id,7622',
                        // 'unique:receipts,temporary_receipt_no',
                        // 'unique:receipts,permanent_receipt_no',
                        'unique:job_order_payment_details,transaction_number',
                    ],

                    'receipt_date' => [
                        'required',
                    ],
                    'receipt_amount' => [
                        'required',
                    ],
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator->errors()->all(),
                    ]);
                }

                if (strtotime($request->receipt_date) < strtotime($request->invoice_date)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'Receipt Date should be greater than or equal to Invoice Date',
                        ],
                    ]);
                }
                // dd($request->receipt_amount,$request->balance_amount);
                if ($request->receipt_amount != $request->balance_amount) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'Receipt amount should be equal to Remaining Invoice amount',
                        ],
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

                $invoice_amount = GigoManualInvoice::where('invoiceable_type', 'App\JobOrder')->where('invoiceable_id', $job_order->id)->sum('amount');

                $receipt_amount = $request->receipt_amount;

                // if ($receipt_amount != $invoice_amount) {
                //     return response()->json([
                //         'success' => false,
                //         'error' => 'Validation Error',
                //         'errors' => [
                //             'Receipt amount should be equal to Invoice amount!',
                //         ],
                //     ]);
                // }

                DB::beginTransaction();

                $job_order->vehicle_payment_status = 1;
                $job_order->updated_by_id = Auth::user()->id;
                $job_order->updated_at = Carbon::now();
                $job_order->status_id = 8468;
                $job_order->save();

                //Save Receipt
                $customer = Customer::find($job_order->customer_id);

                if ($job_order->pending_reason_id == 2 || $job_order->pending_reason_id == 3) {
                    $payment_mode_id = 9;
                } elseif ($job_order->pending_reason_id == 4) {
                    $payment_mode_id = 10;
                } elseif ($job_order->pending_reason_id == 5) {
                    $payment_mode_id = 11;
                } else {
                    $payment_mode_id = $request->payment_mode_id;
                }
                //Delete previous receipt
                // $remove_receipt = Receipt::where('receipt_of_id', 7622)->where('entity_id', $job_order->id)->forceDelete();

                // $receipt = new Receipt;
                // $receipt->company_id = Auth::user()->company_id;
                // $receipt->temporary_receipt_no = $request->receipt_number;
                // $receipt->date = date('Y-m-d', strtotime($request->receipt_date));
                // $receipt->outlet_id = $job_order->outlet_id;
                // $receipt->receipt_of_id = 7622;
                // $receipt->entity_id = $job_order->id;
                // $receipt->permanent_receipt_no = $request->receipt_number;
                // $receipt->amount = $request->receipt_amount;
                // $receipt->settled_amount = $request->receipt_amount;
                // $receipt->payment_mode_id = $payment_mode_id;
                // $receipt->created_at = Carbon::now();

                // $customer->receipt()->save($receipt);

                // $receipt_id = $customer->receipt ? $customer->receipt[0] ? $customer->receipt[0]->id : null : null;

                //Save Payment
                $payment = new Payment;
                // dd($payment);
                $payment->entity_type_id = 8434;
                $payment->entity_id = $job_order->id;
                $payment->received_amount = $request->receipt_amount;
                $payment->receipt_id = NULL;
                $job_order->payment()->save($payment);

                //save payment detail
                $payment = new JobOrderPaymentDetail;
                $payment->payment_mode_id = $payment_mode_id;
                $payment->job_order_id = $job_order->id;
                $payment->transaction_number = $request->receipt_number;
                $payment->transaction_date = date('Y-m-d', strtotime($request->receipt_date));
                $payment->amount = $request->receipt_amount;
                $payment->save();

                //Updare Invoice
                // $update_invoice = GigoManualInvoice::where('invoiceable_type', 'App\JobOrder')->where('invoiceable_id', $job_order->id)->update(['receipt_id' => $receipt_id]);

                //CREATE DIRECTORY TO STORAGE PATH
                $attachment_path = storage_path('app/public/gigo/job_order/attachments/');
                Storage::makeDirectory($attachment_path, 0777);

                if (!empty($request->transaction_attachments)) {
                    foreach ($request->transaction_attachments as $key => $transaction_attachment) {
                        $value = rand(1, 20);
                        $image = $transaction_attachment;

                        $file_name_with_extension = $image->getClientOriginalName();
                        $file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
                        $extension = $image->getClientOriginalExtension();
                        $name = $job_order->id . '_Transcation_Attachment_' . date('Y_m_d_h_i_s') . '_' . $value . '.' . $extension;

                        $transaction_attachment->move(storage_path('app/public/gigo/job_order/attachments/'), $name);
                        $attachement = new Attachment;
                        $attachement->attachment_of_id = 227; //Job order
                        $attachement->attachment_type_id = 11342; //GIGO Transcation Attachment
                        $attachement->entity_id = $job_order->id;
                        $attachement->name = $name;
                        $attachement->save();
                    }
                }
                DB::commit();

                $message = 'Receipt Details saved succesfully!';

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

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

    public function vehiceRequestMail($job_order_id,$type)
    {
        $job_order = JobOrder::with([
            'vehicle',
            'vehicle.model',
            'vehicle.currentOwner.customer',
            'vehicle.currentOwner.customer.address',
            'vehicle.currentOwner.customer.address.country',
            'vehicle.currentOwner.customer.address.state',
            'vehicle.currentOwner.customer.address.city',
            'vehicle.currentOwner.ownershipType',
            'outlet',
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
            'outlet',
            'vehicleDeliveryRequestUser',
        ])
            ->select([
                'job_orders.*',
                DB::raw('DATE_FORMAT(job_orders.created_at,"%d-%m-%Y") as date'),
                DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
            ])
            ->find($job_order_id);

        $total_amount = $job_order->manualDeliveryLabourInvoice->amount + $job_order->manualDeliveryPartsInvoice->amount;
        // dd($job_order->outlet,$job_order->vehicleDeliveryRequestUser);
        $job_order->total_amount = $total_amount;
        if ($job_order) {
            $user_details = MailConfiguration::where('config_id', 3011)->pluck('to_email')->first();
            $to_email = explode(',', $user_details);
            if (!$user_details || count($to_email) == 0) {
                $to_email = ['0' => 'parthiban@uitoux.in'];
            }

            $user_details = MailConfiguration::where('config_id', 3011)->pluck('cc_email')->first();
            $cc_email = explode(',', $user_details);
            if (!$user_details || count($cc_email) == 0) {
                $cc_email = ['0' => 'parthiban@uitoux.in'];
            }

            $outlet = $job_order->outlet->ax_name ? $job_order->outlet->ax_name : $job_order->outlet->name;

            $subject = 'GIGO '.$outlet.' - '. $job_order->vehicle->currentOwner->customer->name.' vehicle need approval for delivery';

            if($type == 2){
                $to_email = [];
                $user = User::where('id',$job_order->vehicle_delivery_requester_id)->first();
                $cc_email = [];
                if($user && $user->email){
                    $to_email = ['0' => $user->email];
                }
                if($job_order->status_id == 8478){
                    $subject = 'GIGO '.$outlet.' - '. $job_order->vehicle->currentOwner->customer->name.' vehicle approved for delivery';
                }else{
                    $subject = 'GIGO '.$outlet.' - '. $job_order->vehicle->currentOwner->customer->name.' vehicle rejected for delivery';
                }
            }

            $to_email = [];
            $to_email = ['0' => 'parthiban@uitoux.in'];

            // dd($subject);
            if ($to_email) {
                // $cc_email = [];
                $approver_view_url = url('/') . '/#!/manual-vehicle-delivery/view/' . $job_order->id;
                $arr['job_order'] = $job_order;
                // $arr['subject'] = 'GIGO – Need approval for Vehicle Delivery';
                $arr['subject'] = $subject;
                $arr['to_email'] = $to_email;
                $arr['cc_email'] = $cc_email;
                $arr['type'] = $type;
                $arr['approver_view_url'] = $approver_view_url;

                $MailInstance = new VehicleDeliveryRequestMail($arr);
                $Mail = Mail::send($MailInstance);
            }
        }
    }

    public function generateGatePass($job_order)
    {
        // dd($job_order);
        $gate_log = GateLog::where('job_order_id', $job_order->id)->orderBy('id', 'DESC')->first();
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

                    if ($gate_pass->exists) {
                        $gate_pass->updated_at = Carbon::now();
                        $gate_pass->updated_by_id = Auth::user()->id;
                    } else {
                        $gate_log->status_id = 8123; //GATE OUT PENDING
                        $gate_pass->status_id = 8340; //GATE OUT PENDING
                        $gate_pass->created_at = Carbon::now();
                        $gate_pass->created_by_id = Auth::user()->id;
                    }

                    $gate_pass->gate_pass_of_id = 11280;
                    $gate_pass->entity_id = $job_order->id;

                    $gate_pass->company_id = $gate_log->company_id;
                    $gate_pass->number = $generateNumber['number'];
                    $gate_pass->save();

                    $gate_log->gate_pass_id = $gate_pass->id;
                    $gate_log->updated_by_id = Auth::user()->id;
                    $gate_log->updated_at = Carbon::now();
                    $gate_log->save();
                }

                //Generate GatePass PDF
                $generate_estimate_gatepass_pdf = JobOrder::generateEstimateGatePassPDF($job_order->id, $type = 'GateIn');
                // $generate_covering_pdf = JobOrder::generateCoveringLetterPDF($job_order->id);
            }
        }

        return true;
    }
}
