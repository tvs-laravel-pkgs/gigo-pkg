<?php

namespace Abs\GigoPkg\Api;

use Abs\SerialNumberPkg\SerialNumberGroup;
use App\Config;
use App\Country;
use App\Customer;
use App\Employee;
use App\FinancialYear;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WpoSoapController;
use App\JobOrder;
use App\OnSiteOrder;
use App\OnSiteOrderEstimate;
use App\OnSiteOrderIssuedPart;
use App\OnSiteOrderPart;
use App\OnSiteOrderRepairOrder;
use App\OnSiteOrderReturnedPart;
use App\Outlet;
use App\Part;
use App\PartStock;
use App\RepairOrder;
use App\SplitOrderType;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
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

    public function getLabourPartsData($params)
    {

        $result = array();

        $site_visit = OnSiteOrder::with([
            'company',
            'outlet',
            'onSiteVisitUser',
            'customer',
            'customer.address',
            'customer.address.country',
            'customer.address.state',
            'customer.address.city',
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

        $result['site_visit'] = $site_visit;
        $result['labour_details'] = $labour_details;
        $result['part_details'] = $part_details;
        $result['labour_amount'] = $labour_amount;
        $result['part_amount'] = $part_amount;
        $result['total_amount'] = $total_amount;
        $result['labours'] = $labours;

        return $result;
    }

    public function getFormData(Request $request)
    {
        // dd($request->all());
        if ($request->id) {
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
        } else {
            $site_visit = new OnSiteOrder;
            // $previous_number = OnSiteOrder::where('outlet_id',Auth::user()->working_outlet_id)->orderBy('id','desc')->first();
            // $site_visit->number =
            $result['site_visit'] = $site_visit;
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
            'site_visit' => $result['site_visit'],
            'part_details' => $result['part_details'],
            'labour_details' => $result['labour_details'],
            'total_amount' => $result['total_amount'],
            'labour_amount' => $result['labour_amount'],
            'parts_rate' => $result['part_amount'],
            'labours' => $result['labours'],
            'extras' => $extras,
            'country' => Country::find(1),
        ]);
    }

    public function saveLabourDetail(Request $request)
    {
        // dd($request->all());
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
            // $on_site_order->status_id = 8463;
            $on_site_order->save();

            $estimate_id = OnSiteOrderEstimate::where('on_site_order_id', $on_site_order->id)->where('status_id', 10071)->first();
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

                $estimate = new OnSiteOrderEstimate;
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
            if ($request->repair_order_description) {
                $on_site_repair_order->amount = $request->repair_order_amount;
            } else {
                $on_site_repair_order->amount = $repair_order->amount;
            }

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

    public function savePartsDetail(Request $request)
    {
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

            $estimate_id = OnSiteOrderEstimate::where('on_site_order_id', $on_site_order->id)->where('status_id', 10071)->first();
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

                $estimate = new OnSiteOrderEstimate;
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

    public function save(Request $request)
    {
        // dd($request->all());
        try {

            $error_messages = [
                'customer_remarks.required' => "Customer Remarks is required",
            ];
            $validator = Validator::make($request->all(), [
                'customer_remarks' => [
                    'required',
                ],
                'planned_visit_date' => [
                    'required',
                ],
                'code' => [
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

            DB::beginTransaction();

            if ($request->on_site_order_id) {
                $site_visit = OnSiteOrder::find($request->on_site_order_id);
                if (!$site_visit) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'Site Visit Detail Not Found!',
                        ],
                    ]);
                }
                $site_visit->updated_by_id = Auth::id();
                $site_visit->updated_at = Carbon::now();
            } else {
                $site_visit = new OnSiteOrder;
                $site_visit->company_id = Auth::user()->company_id;
                $site_visit->outlet_id = Auth::user()->working_outlet_id;
                $site_visit->on_site_visit_user_id = Auth::user()->id;

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
                $branch = Outlet::where('id', Auth::user()->working_outlet_id)->first();

                //GENERATE GATE IN VEHICLE NUMBER
                $generateNumber = SerialNumberGroup::generateNumber(152, $financial_year->id, $branch->state_id, $branch->id);
                if (!$generateNumber['success']) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'No Site Visit number found for FY : ' . $financial_year->year . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
                        ],
                    ]);
                }

                // dd($generateNumber);
                $site_visit->number = $generateNumber['number'];
                $site_visit->created_by_id = Auth::id();
                $site_visit->created_at = Carbon::now();
                $site_visit->status_id = 1;
            }

            //save customer
            $customer = Customer::saveCustomer($request->all());
            $customer->saveAddress($request->all());

            $site_visit->customer_id = $customer->id;
            $site_visit->planned_visit_date = date('Y-m-d', strtotime($request->planned_visit_date));
            $site_visit->customer_remarks = $request->customer_remarks;

            $site_visit->save();

            $message = "On Site Visit Saved Successfully!";

            DB::commit();

            //Send Approved Mail for user
            // $this->vehiceRequestMail($job_order->id, $type = 2);

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
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

    //BULK ISSUE PART FORM DATA
    public function getBulkIssuePartFormData(Request $request)
    {
        // dd($request->all());
        try {
            $site_visit = OnSiteOrder::with([
                'company',
                'outlet',
                'onSiteVisitUser',
                'customer',
                'customer.address',
                'customer.address.country',
                'customer.address.state',
                'customer.address.city',
                'outlet',
                'status',
                'onSiteOrderRepairOrders',
                'onSiteOrderParts',
            ])->find($request->id);

            if (!$site_visit) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'On Site Visit Not Found!',
                    ],
                ]);
            }

            $on_site_order_parts = Part::join('on_site_order_parts', 'on_site_order_parts.part_id', 'parts.id')->where('on_site_order_parts.on_site_order_id', $request->id)->whereNull('on_site_order_parts.removal_reason_id')
            // ->where('on_site_order_parts.is_customer_approved', 1)
                ->select('on_site_order_parts.id as on_site_order_part_id', 'on_site_order_parts.qty', 'parts.code', 'parts.name', 'parts.id')->get();

            $parts_data = array();

            // dd($on_site_order_parts);
            if ($on_site_order_parts) {
                foreach ($on_site_order_parts as $key => $parts) {
                    // dump($parts->code, $parts->id);

                    //Issued Qty
                    $issued_qty = OnSiteOrderIssuedPart::where('on_site_order_part_id', $parts->on_site_order_part_id)->sum('issued_qty');

                    //Returned Qty
                    $returned_qty = OnSiteOrderReturnedPart::where('on_site_order_part_id', $parts->on_site_order_part_id)->sum('returned_qty');

                    //Available Qty
                    $avail_qty = PartStock::where('part_id', $parts->id)->where('outlet_id', $site_visit->outlet_id)->pluck('stock')->first();

                    $total_remain_qty = ($parts->qty + $returned_qty) - $issued_qty;
                    $total_issued_qty = $issued_qty - $returned_qty;

                    // dump($avail_qty, $total_remain_qty);
                    // if ($avail_qty && $avail_qty > 0 && $total_remain_qty > 0) {
                    if ($total_remain_qty > 0) {
                        $parts_data[$key]['part_id'] = $parts->id;
                        $parts_data[$key]['code'] = $parts->code;
                        $parts_data[$key]['name'] = $parts->name;
                        $parts_data[$key]['on_site_order_part_id'] = $parts->on_site_order_part_id;
                        $parts_data[$key]['total_avail_qty'] = $avail_qty;
                        $parts_data[$key]['total_request_qty'] = $parts->qty;
                        $parts_data[$key]['total_issued_qty'] = $total_issued_qty;
                        $parts_data[$key]['total_remaining_qty'] = $total_remain_qty;
                    }
                }
            }

            $responseArr = array(
                'success' => true,
                'site_visit' => $site_visit,
                'on_site_order_parts' => $parts_data,
                'mechanic_id' => $site_visit->on_site_visit_user_id,
                // 'issue_modes' => $issue_modes,
            );

            return response()->json($responseArr);
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

    //SAVE ISSUED PART
    public function saveIssuedPart(Request $request)
    {
        // dd($request->all());
        try {
            if ($request->part_type == 3) {
                $validator = Validator::make($request->all(), [
                    'on_site_order_id' => [
                        'required',
                        'exists:on_site_orders,id',
                    ],

                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator->errors()->all(),
                    ]);
                }

                DB::beginTransaction();

                if ($request->issued_part) {
                    foreach ($request->issued_part as $key => $issued_part) {
                        if (isset($issued_part['qty'])) {
                            $on_site_order_isssued_part = new OnSiteOrderIssuedPart;
                            $on_site_order_isssued_part->on_site_order_part_id = $issued_part['on_site_order_part_id'];

                            $on_site_order_isssued_part->issued_qty = $issued_part['qty'];
                            $on_site_order_isssued_part->issued_mode_id = 8480;
                            $on_site_order_isssued_part->issued_to_id = $request->issued_to_id;
                            $on_site_order_isssued_part->created_by_id = Auth::user()->id;
                            $on_site_order_isssued_part->created_at = Carbon::now();
                            $on_site_order_isssued_part->save();

                            $on_site_order_part = OnSiteOrderPart::find($issued_part['on_site_order_part_id']);
                            $on_site_order_part->status_id = 8202; //Issued
                            $on_site_order_part->save();
                        }
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => ['Parts not found!'],
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Part Issued Successfully!!',
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

    //PART DATA
    public function returnParts(Request $request)
    {
        // dd($request->all());
        try {
            if ($request->type_id == 2) {
                $validator = Validator::make($request->all(), [
                    'on_site_order_id' => [
                        'required',
                        'exists:on_site_orders,id',
                    ],
                    'on_site_order_part_id' => [
                        'required',
                        'exists:on_site_order_parts,id',
                    ],

                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator->errors()->all(),
                    ]);
                }

                $site_visit = OnSiteOrder::find($request->on_site_order_id);

                if (!$site_visit) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'On Site Visit Not Found!',
                        ],
                    ]);
                }

                if ($request->returned_qty) {
                    DB::beginTransaction();

                    //Total Qty
                    $parts = OnSiteOrderPart::where('id', $request->on_site_order_part_id)->first();
                    $total_qty = $parts->qty;

                    //Issued Qty
                    $issued_qty = OnSiteOrderIssuedPart::where('on_site_order_part_id', $parts->id)->sum('issued_qty');

                    //Returned Qty
                    $returned_qty = OnSiteOrderReturnedPart::where('on_site_order_part_id', $parts->id)->sum('returned_qty');

                    $total_remain_qty = ($issued_qty + $returned_qty);

                    if ($total_remain_qty >= $request->returned_qty) {
                        $returned_part = new OnSiteOrderReturnedPart;
                        $returned_part->on_site_order_part_id = $parts->id;
                        $returned_part->returned_qty = $request->returned_qty;
                        $returned_part->returned_to_id = $site_visit->on_site_visit_user_id;
                        $returned_part->remarks = $request->remarks;
                        $returned_part->created_by_id = Auth::user()->id;
                        $returned_part->created_at = Carbon::now();
                        $returned_part->save();
                    } else {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => [
                                'Invalid Returned Qty!',
                            ],
                        ]);
                    }

                    DB::commit();
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'Invalid Returned Qty!',
                        ],
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Part Returned Successfully!!',
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
    //PART DATA
    public function getPartsData(Request $request)
    {
        // dd($request->all());
        try {
            $site_visit = OnSiteOrder::find($request->id);

            if (!$site_visit) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'On Site Visit Not Found!',
                    ],
                ]);
            }

            $on_site_order_parts = OnSiteOrderPart::join('parts', 'on_site_order_parts.part_id', 'parts.id')->where('on_site_order_parts.on_site_order_id', $request->id)->whereNull('on_site_order_parts.removal_reason_id')->select('parts.name', 'parts.code', 'on_site_order_parts.id as on_site_order_part_id')->get();

            $part_logs = array();
            $issued_parts = 0;

            if ($on_site_order_parts) {

                $parts_issue_logs = OnSiteOrderIssuedPart::join('on_site_order_parts', 'on_site_order_parts.id', 'on_site_order_issued_parts.on_site_order_part_id')
                    ->join('parts', 'on_site_order_parts.part_id', 'parts.id')
                    ->join('configs', 'on_site_order_issued_parts.issued_mode_id', 'configs.id')
                    ->join('users', 'on_site_order_issued_parts.issued_to_id', 'users.id')
                    ->where('on_site_order_parts.on_site_order_id', $request->id)
                    ->select(DB::raw('"Part Issued" as transaction_type'),
                        'parts.name',
                        'parts.code',
                        'on_site_order_issued_parts.issued_qty as qty',
                        DB::raw('"-" as remarks'),
                        DB::raw('DATE_FORMAT(on_site_order_issued_parts.created_at,"%d/%m/%Y") as date'),
                        'configs.name as issue_mode',
                        'users.name as mechanic',
                        'on_site_order_issued_parts.id as on_site_order_issued_part_id',
                        'users.id as employee_id',
                        'on_site_order_parts.id as job_order_part_issue_return_id',
                        'parts.id as part_id')
                // ->get()
                ;

                $issued_parts = $parts_issue_logs->get()->count();

                $parts_return_logs = OnSiteOrderReturnedPart::join('on_site_order_parts', 'on_site_order_parts.id', 'on_site_order_returned_parts.on_site_order_part_id')
                    ->join('parts', 'on_site_order_parts.part_id', 'parts.id')
                    ->join('users', 'on_site_order_returned_parts.returned_to_id', 'users.id')
                    ->select(
                        DB::raw('"Part Returned" as transaction_type'),
                        'parts.name',
                        'parts.code',
                        'on_site_order_returned_parts.returned_qty as qty',
                        'on_site_order_returned_parts.remarks',
                        DB::raw('DATE_FORMAT(on_site_order_returned_parts.created_at,"%d/%m/%Y") as date'),
                        DB::raw('"-" as issue_mode'),
                        'users.name as mechanic',
                        'on_site_order_returned_parts.id as on_site_order_issued_part_id',
                        'users.id as employee_id',
                        'on_site_order_returned_parts.id as job_order_part_issue_return_id',
                        'parts.id as part_id'

                    )->union($parts_issue_logs)->orderBy('date', 'DESC')->get();

                $part_logs = $parts_return_logs;
            }

            return response()->json([
                'success' => true,
                'part_logs' => $part_logs,
                // 'issued_parts' => $issued_parts,
                'on_site_order_parts' => $on_site_order_parts,
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

    public function sendRequestPartsIntent(Request $request)
    {
        // dd($request->all());
        try {
            $site_visit = OnSiteOrder::find($request->id);

            if (!$site_visit) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Site Visit Not Found!',
                    ],
                ]);
            }

            DB::beginTransaction();

            if ($request->type_id == 1) {
                $site_visit->status_id = 4;
            } elseif ($request->type_id == 2) {
                $site_visit->status_id = 5;
            } else {
                $site_visit->status_id = 8;
            }

            $site_visit->updated_by_id = Auth::user()->id;
            $site_visit->updated_at = Carbon::now();
            $site_visit->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'On Site Visit Updated Successfully!!',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Server Network Down!',
                'errors' => ['Exception Error' => $e->getMessage()],
            ]);
        }
    }
}
