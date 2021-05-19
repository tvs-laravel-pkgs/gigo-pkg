<?php

namespace Abs\GigoPkg;

use Abs\AmcPkg\AmcPolicy;
use App\AmcAggregateCoupon;
use App\Config;
use App\Customer;
use App\Http\Controllers\Controller;
use App\JobOrder;
use App\TvsOneApprovalStatus;
use App\VehicleDeliveryStatus;
use App\VehicleModel;
use Auth;
use DB;
use Entrust;
use Excel;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class ManualVehicleDeliveryController extends Controller
{

    public function __construct()
    {
        $this->data['theme'] = config('custom.theme');
    }

    public function getManualDeliveryVehicleFilter()
    {
        $params = [
            'config_type_id' => 49,
            'add_default' => true,
            'default_text' => "Select Status",
        ];
        $this->data['extras'] = [
            'registration_type_list' => [
                ['id' => '', 'name' => 'Select Registration Type'],
                ['id' => '1', 'name' => 'Registered Vehicle'],
                ['id' => '0', 'name' => 'Un-Registered Vehicle'],
            ],
            'status_list' => Config::getDropDownList($params),
            'vehicle_delivery_status_list' => VehicleDeliveryStatus::where('company_id', Auth::user()->company_id)->where('id', '!=', 3)->get(),
            'tvs_one_request_status_list' => collect(TvsOneApprovalStatus::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Status']),
            'policies_list' => collect(AmcPolicy::select('type', 'id')->whereIn('id', [1, 3])->get())->prepend(['id' => '', 'type' => 'Select Policy']),
        ];

        return response()->json($this->data);
    }

    public function getManualDeliveryVehicleList(Request $request)
    {
        // dd($request->all());
        if ($request->date_range) {
            $date_range = explode(' to ', $request->date_range);
            $start_date = date('Y-m-d', strtotime($date_range[0]));
            $start_date = $start_date . ' 00:00:00';

            $end_date = date('Y-m-d', strtotime($date_range[1]));
            $end_date = $end_date . ' 23:59:59';
        } else {
            $start_date = date('Y-m-01 00:00:00');
            $end_date = date('Y-m-t 23:59:59');
        }

        $vehicle_inwards = JobOrder::join('gate_logs', 'gate_logs.job_order_id', 'job_orders.id')
            ->leftJoin('vehicles', 'job_orders.vehicle_id', 'vehicles.id')
            ->leftJoin('vehicle_owners', function ($join) {
                $join->on('vehicle_owners.vehicle_id', 'job_orders.vehicle_id')
                    ->whereRaw('vehicle_owners.from_date = (select MAX(vehicle_owners1.from_date) from vehicle_owners as vehicle_owners1 where vehicle_owners1.vehicle_id = job_orders.vehicle_id)');
            })
            ->leftJoin('customers', 'customers.id', 'vehicle_owners.customer_id')
            ->leftJoin('models', 'models.id', 'vehicles.model_id')
            ->leftJoin('amc_members', 'amc_members.vehicle_id', 'vehicles.id')
            ->leftJoin('amc_policies', 'amc_policies.id', 'amc_members.policy_id')
            ->leftJoin('vehicle_delivery_statuses', 'vehicle_delivery_statuses.id', 'job_orders.vehicle_delivery_status_id')
            ->leftJoin('configs as discount_status', 'discount_status.id', 'job_orders.customer_approval_status_id')
            ->join('configs', 'configs.id', 'job_orders.status_id')
            ->join('outlets', 'outlets.id', 'job_orders.outlet_id')
            ->select(
                'job_orders.id',
                DB::raw('IF(vehicles.is_registered = 1,"Registered Vehicle","Un-Registered Vehicle") as registration_type'),
                'vehicles.registration_number',
                DB::raw('COALESCE(models.model_number, "-") as model_number'),
                'gate_logs.number',
                'job_orders.status_id',
                'job_orders.customer_approval_status_id',
                DB::raw('DATE_FORMAT(gate_logs.gate_in_date,"%d/%m/%Y, %h:%i %p") as date'),
                'job_orders.driver_name',
                'job_orders.driver_mobile_number as driver_mobile_number',
                'job_orders.is_customer_agreed',
                DB::raw('COALESCE(CONCAT(amc_policies.name,"/",amc_policies.type), "-") as amc_policies'),
                'configs.name as status',
                'outlets.code as outlet_code',
                // DB::raw('COALESCE(customers.name, "-") as customer_name'),
                DB::raw('CONCAT(customers.code, " / ",customers.name) as customer_name'),
                'job_orders.vehicle_delivery_status_id',
                'customers.mobile_no',
                DB::raw('IF(job_orders.vehicle_delivery_status_id IS NULL,"WIP",vehicle_delivery_statuses.name) as vehicle_status'), 'discount_status.name as discount_approval_status'
            )
        // ->where(function ($query) use ($start_date, $end_date) {
        //     $query->whereDate('gate_logs.gate_in_date', '>=', $start_date)
        //         ->whereDate('gate_logs.gate_in_date', '<=', $end_date);
        // })
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

        if ($request->date_range) {
            $vehicle_inwards->whereDate('gate_logs.gate_in_date', '>=', $start_date)->whereDate('gate_logs.gate_in_date', '<=', $end_date);
        }

        if (!Entrust::can('view-all-outlet-manual-vehicle-delivery')) {
            if (Entrust::can('view-mapped-outlet-manual-vehicle-delivery')) {
                $outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
                array_push($outlet_ids, Auth::user()->employee->outlet_id);
                $vehicle_inwards->whereIn('job_orders.outlet_id', $outlet_ids);
            } else {
                $vehicle_inwards->where('job_orders.outlet_id', Auth::user()->working_outlet_id);
            }
        }

        if (Entrust::can('verify-manual-vehicle-delivery')) {
            $vehicle_inwards->whereIn('job_orders.status_id', [8477]);
        }

        $vehicle_inwards->groupBy('job_orders.id');
        $vehicle_inwards->orderBy('gate_logs.gate_in_date', 'DESC');
        // $vehicle_inwards->orderBy('job_orders.status_id', 'DESC');

        return Datatables::of($vehicle_inwards)
            ->rawColumns(['status', 'action'])
            ->filterColumn('registration_type', function ($query, $keyword) {
                $sql = 'IF(vehicles.is_registered = 1,"Registered Vehicle","Un-Registered Vehicle")  like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->editColumn('status', function ($vehicle_inward) {
                $status = $vehicle_inward->status_id == '8460' || $vehicle_inward->status_id == '8469' || $vehicle_inward->status_id == '8477' || $vehicle_inward->status_id == '8479' ? 'green' : 'blue';
                return '<span class="text-' . $status . '">' . $vehicle_inward->status . '</span>';
            })
            ->editColumn('vehicle_status', function ($vehicle_inward) {
                $status = 'blue';
                if ($vehicle_inward->vehicle_delivery_status_id == 3) {
                    $status = 'green';
                } elseif ($vehicle_inward->vehicle_delivery_status_id == 2 || $vehicle_inward->vehicle_delivery_status_id == 4) {
                    $status = 'red';
                }
                return '<span class="text-' . $status . '">' . $vehicle_inward->vehicle_status . '</span>';
            })
            ->editColumn('discount_approval_status', function ($vehicle_inward) {
                $status = 'blue';
                if ($vehicle_inward->customer_approval_status_id == 11851) {
                    $status = 'green';
                } elseif ($vehicle_inward->customer_approval_status_id == 11852) {
                    $status = 'red';
                }
                return '<span class="text-' . $status . '">' . $vehicle_inward->discount_approval_status . '</span>';
            })
            ->addColumn('action', function ($vehicle_inward) {
                $view_img = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
                $edit_img = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');

                $status_img = asset('public/theme/img/table/add-new-invoice.svg');
                $status_img_hover = asset('public/theme/img/table/add-hover.svg');

                $otp_img = asset('public/theme/img/table/options.svg');
                $otp_img_hover = asset('public/theme/img/table/options-active.svg');

                $output = '';

                if ($vehicle_inward->vehicle_delivery_status_id != 3 && $vehicle_inward->vehicle_delivery_status_id != 4 && !Entrust::can('verify-manual-vehicle-delivery')) {
                    $output .= '<a href="javascript:;" data-toggle="modal" data-target="#change_vehicle_status" onclick="angular.element(this).scope().changeStatus(' . $vehicle_inward->id . ',' . $vehicle_inward->vehicle_delivery_status_id . ')" title="Change Vehicle Status"><img src="' . $status_img . '" alt="Change Vehicle Status" class="img-responsive delete" onmouseover=this.src="' . $status_img_hover . '" onmouseout=this.src="' . $status_img . '"></a>
					';
                }

                if ($vehicle_inward->status_id != 8478 && $vehicle_inward->status_id != 8477 && $vehicle_inward->status_id != 8467 && $vehicle_inward->status_id != 8468 && $vehicle_inward->status_id != 8470 && !Entrust::can('verify-manual-vehicle-delivery')) {
                    $output .= '<a href="#!/manual-vehicle-delivery/form/' . $vehicle_inward->id . '" id = "" title="Form"><img src="' . $edit_img . '" alt="View" class="img-responsive" onmouseover=this.src="' . $edit_img . '" onmouseout=this.src="' . $edit_img . '"></a>';
                }
                $output .= '<a href="#!/manual-vehicle-delivery/view/' . $vehicle_inward->id . '" id = "" title="View"><img src="' . $view_img . '" alt="View" class="img-responsive" onmouseover=this.src="' . $view_img . '" onmouseout=this.src="' . $view_img . '"></a>';

                if ($vehicle_inward->customer_approval_status_id == 11850 && !Entrust::can('verify-manual-vehicle-delivery')) {
                    $output .= '<a href="javascript:;" data-toggle="modal" data-target="#otp-modal" onclick="angular.element(this).scope().updateApprovalStatus(' . $vehicle_inward->id . ',' . $vehicle_inward->mobile_no . ')" title="Change Vehicle Status"><img src="' . $otp_img . '" alt="Change Vehicle Status" class="img-responsive delete" onmouseover=this.src="' . $otp_img_hover . '" onmouseout=this.src="' . $otp_img . '"></a>
					';
                }

                return $output;
            })
            ->make(true);
    }

    public function getTVSOneRequestList(Request $request)
    {
        // dd($request->all());
        if ($request->date_range) {
            $date_range = explode(' to ', $request->date_range);
            $start_date = date('Y-m-d', strtotime($date_range[0]));
            $start_date = $start_date . ' 00:00:00';

            $end_date = date('Y-m-d', strtotime($date_range[1]));
            $end_date = $end_date . ' 23:59:59';
        } else {
            $start_date = date('Y-m-01 00:00:00');
            $end_date = date('Y-m-t 23:59:59');
        }

        $vehicle_inwards = JobOrder::join('gate_logs', 'gate_logs.job_order_id', 'job_orders.id')
            ->join('vehicles', 'job_orders.vehicle_id', 'vehicles.id')
            ->leftJoin('vehicle_owners', function ($join) {
                $join->on('vehicle_owners.vehicle_id', 'job_orders.vehicle_id')
                    ->whereRaw('vehicle_owners.from_date = (select MAX(vehicle_owners1.from_date) from vehicle_owners as vehicle_owners1 where vehicle_owners1.vehicle_id = job_orders.vehicle_id)');
            })
            ->leftJoin('customers', 'customers.id', 'vehicle_owners.customer_id')
            ->leftJoin('models', 'models.id', 'vehicles.model_id')
            ->leftJoin('amc_members', 'amc_members.vehicle_id', 'vehicles.id')
            ->leftJoin('amc_policies', 'amc_policies.id', 'amc_members.policy_id')
            ->leftJoin('vehicle_delivery_statuses', 'vehicle_delivery_statuses.id', 'job_orders.vehicle_delivery_status_id')
            ->join('tvs_one_approval_statuses', 'tvs_one_approval_statuses.id', 'job_orders.tvs_one_approval_status_id')
            ->join('outlets', 'outlets.id', 'job_orders.outlet_id')
            ->select(
                'job_orders.id',
                DB::raw('IF(vehicles.is_registered = 1,"Registered Vehicle","Un-Registered Vehicle") as registration_type'),
                'vehicles.registration_number',
                DB::raw('COALESCE(models.model_number, "-") as model_number'),
                'gate_logs.number',
                'job_orders.tvs_one_approval_status_id',
                DB::raw('DATE_FORMAT(gate_logs.gate_in_date,"%d/%m/%Y, %h:%i %p") as date'),
                'job_orders.driver_name',
                'job_orders.driver_mobile_number as driver_mobile_number',
                'job_orders.is_customer_agreed',
                DB::raw('COALESCE(CONCAT(amc_policies.name,"/",amc_policies.type), "-") as amc_policies'),
                'tvs_one_approval_statuses.name as status',
                'outlets.code as outlet_code',
                DB::raw('CONCAT(customers.code, " / ", customers.name) as customer_name'),
                'job_orders.vehicle_delivery_status_id',
                DB::raw('IF(job_orders.vehicle_delivery_status_id IS NULL,"WIP",vehicle_delivery_statuses.name) as vehicle_status')
            )
        // ->where(function ($query) use ($start_date, $end_date) {
        //     $query->whereDate('gate_logs.gate_in_date', '>=', $start_date)
        //         ->whereDate('gate_logs.gate_in_date', '<=', $end_date);
        // })
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
        // ->where('job_orders.pending_reason_id', 2)
        ;

        if ($request->date_range) {
            $vehicle_inwards->whereDate('gate_logs.gate_in_date', '>=', $start_date)->whereDate('gate_logs.gate_in_date', '<=', $end_date);
        }

        if (!Entrust::can('view-all-outlet-tvs-one-discount-request')) {
            if (Entrust::can('view-mapped-outlet-tvs-one-discount-request')) {
                $outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
                array_push($outlet_ids, Auth::user()->employee->outlet_id);
                $vehicle_inwards->whereIn('job_orders.outlet_id', $outlet_ids);
            } else {
                $vehicle_inwards->where('job_orders.outlet_id', Auth::user()->working_outlet_id);
            }
        }

        // if (Entrust::can('verify-manual-vehicle-delivery')) {
        $vehicle_inwards->whereIn('job_orders.tvs_one_approval_status_id', [1, 2, 3]);
        // }

        $vehicle_inwards->groupBy('job_orders.id');
        $vehicle_inwards->orderBy('gate_logs.gate_in_date', 'DESC');
        // $vehicle_inwards->orderBy('job_orders.status_id', 'DESC');

        return Datatables::of($vehicle_inwards)
            ->rawColumns(['status', 'action'])
            ->filterColumn('registration_type', function ($query, $keyword) {
                $sql = 'IF(vehicles.is_registered = 1,"Registered Vehicle","Un-Registered Vehicle")  like ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->editColumn('status', function ($vehicle_inward) {
                $status = 'blue';
                if ($vehicle_inward->tvs_one_approval_status_id == 2) {
                    $status = 'green';
                } elseif ($vehicle_inward->tvs_one_approval_status_id == 3) {
                    $status = 'red';
                }
                return '<span class="text-' . $status . '">' . $vehicle_inward->status . '</span>';

            })
            ->editColumn('vehicle_status', function ($vehicle_inward) {
                $status = 'blue';
                if ($vehicle_inward->vehicle_delivery_status_id == 3) {
                    $status = 'green';
                } elseif ($vehicle_inward->vehicle_delivery_status_id == 2) {
                    $status = 'red';
                }
                return '<span class="text-' . $status . '">' . $vehicle_inward->vehicle_status . '</span>';
            })
            ->addColumn('action', function ($vehicle_inward) {
                $view_img = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
                $edit_img = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');

                $status_img = asset('public/theme/img/table/add-new-invoice.svg');
                $status_img_hover = asset('public/theme/img/table/add-hover.svg');

                $output = '';

                $output .= '<a href="#!/tvs-one/discount-request/view/' . $vehicle_inward->id . '" id = "" title="View"><img src="' . $view_img . '" alt="View" class="img-responsive" onmouseover=this.src="' . $view_img . '" onmouseout=this.src="' . $view_img . '"></a>';
                return $output;
            })
            ->make(true);
    }

    public function getCustomerSearchList(Request $request)
    {
        return Customer::searchCustomer($request);
    }

    public function getVehicleModelSearchList(Request $request)
    {
        return VehicleModel::searchVehicleModel($request);
    }

    public function export(Request $request)
    {
        ob_end_clean();

        // ini_set('memory_limit', '50M');
        ini_set('max_execution_time', 0);

        // dd($request->all());
        if ($request->date) {
            $date_range = explode(' to ', $request->date);
            $start_date = date('Y-m-d', strtotime($date_range[0]));
            $start_date = $start_date . ' 00:00:00';

            $end_date = date('Y-m-d', strtotime($date_range[1]));
            $end_date = $end_date . ' 23:59:59';
        } else {
            $start_date = date('Y-m-01 00:00:00');
            $end_date = date('Y-m-t 23:59:59');
        }

        $vehicle_inward = JobOrder::with(['manualDeliveryLabourInvoice',
            'manualDeliveryPartsInvoice'])->select('regions.code as region_code', 'states.code as state_code', 'customers.code as customer_code', 'customers.name as customer_name', 'gate_logs.number as gate_in_number', 'gate_logs.gate_in_date', 'gate_logs.gate_out_date', 'vehicles.registration_number', 'vehicles.engine_number', 'vehicles.chassis_number', 'job_orders.inward_cancel_reason_id', 'billing_type.name as billing_type', 'job_orders.warranty_reason', 'inward_cancel.name as inward_cancel_reason_name', 'job_orders.inward_cancel_reason', 'job_orders.vehicle_payment_status', 'pending_reasons.name as pending_reason', 'jv_customers.code as jv_customer_code', 'jv_customers.name as jv_customer_name', 'job_orders.pending_remarks', 'users.ecode as user_code', 'users.name as user_name', 'job_orders.vehicle_delivery_request_remarks', 'job_orders.approved_remarks', 'job_orders.approved_date_time', 'outlets.code as outlet_code', 'outlets.name as outlet_name', 'outlets.ax_name', 'vehicle_delivery_statuses.name as vehicle_delivery_status', 'job_orders.id', 'job_orders.job_card_number', 'job_orders.labour_discount_amount', 'job_orders.part_discount_amount', 'job_order_status.name as status')
            ->join('gate_logs', 'gate_logs.job_order_id', 'job_orders.id')
            ->leftJoin('vehicles', 'job_orders.vehicle_id', 'vehicles.id')
            ->leftJoin('customers', 'customers.id', 'job_orders.customer_id')
            ->leftJoin('models', 'models.id', 'vehicles.model_id')
            ->leftJoin('amc_members', 'amc_members.vehicle_id', 'vehicles.id')
            ->leftJoin('amc_policies', 'amc_policies.id', 'amc_members.policy_id')
            ->leftJoin('vehicle_delivery_statuses', 'vehicle_delivery_statuses.id', 'job_orders.vehicle_delivery_status_id')
            ->leftJoin('configs as billing_type', 'billing_type.id', 'job_orders.billing_type_id')
            ->leftJoin('configs as inward_cancel', 'inward_cancel.id', 'job_orders.inward_cancel_reason_id')
            ->leftJoin('pending_reasons', 'pending_reasons.id', 'job_orders.pending_reason_id')
            ->leftJoin('users', 'users.id', 'job_orders.vehicle_delivery_requester_id')
            ->leftJoin('customers as jv_customers', 'jv_customers.id', 'job_orders.jv_customer_id')
            ->join('configs as job_order_status', 'job_order_status.id', 'job_orders.status_id')
            ->join('outlets', 'outlets.id', 'job_orders.outlet_id')
            ->join('states', 'states.id', 'outlets.state_id')
            ->join('regions', 'regions.state_id', 'states.id')
            ->whereNotNull('job_orders.status_id')
            ->whereDate('gate_logs.gate_in_date', '>=', $start_date)
            ->whereDate('gate_logs.gate_in_date', '<=', $end_date)
            ->orderBy('gate_logs.gate_in_date', 'asc')
            ->groupBy('job_orders.id');

        if (!Entrust::can('view-all-outlet-manual-vehicle-delivery')) {
            if (Entrust::can('view-mapped-outlet-manual-vehicle-delivery')) {
                $outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
                array_push($outlet_ids, Auth::user()->employee->outlet_id);
                $vehicle_inward = $vehicle_inward->whereIn('job_orders.outlet_id', $outlet_ids);
            } else {
                $vehicle_inward = $vehicle_inward->where('job_orders.outlet_id', Auth::user()->working_outlet_id);
            }
        }

        if ($request->status_id) {
            $vehicle_inward = $vehicle_inward->where('job_orders.status_id', $request->status_id);
        }

        $vehicle_inwards = $vehicle_inward->get();
        $vehicle_details = array();

        $header = [
            // 'Sno',
            'State',
            'Region',
            'Outlet',
            'Customer Code',
            'Customer Name',
            'GateIn Number',
            'GateIn Date & Time',
            'GateOut Date & Time',
            'Registration Number',
            'Chassis Number',
            'Engine Number',
            'Vehicle Status',
            'Service Completed',
            'Billing Type',
            'Job Card Number',
            'Invoice Date',
            'Labour Invoice Number',
            'Labour Amount',
            'Labour Discount Amount',
            'Parts Invoice Number',
            'Parts Amount',
            'Parts Discount Amount',
            'Inward Cancel Reason',
            'Remarks',
            // 'Payment Status',

        ];
        // dd(count($vehicle_inward));
        if (count($vehicle_inwards) > 0) {
            $count = 1;
            foreach ($vehicle_inwards as $key => $vehicle_inward) {
                // dd($vehicle_inward);
                $vehicle_detail = array();

                // $vehicle_detail['sno'] = $count;
                $vehicle_detail['state'] = $vehicle_inward->state_code;
                $vehicle_detail['region'] = $vehicle_inward->region_code;
                $vehicle_detail['outlet'] = $vehicle_inward->outlet_code . ' / ' . ($vehicle_inward->ax_name ? $vehicle_inward->ax_name : $vehicle_inward->outlet_name);
                $vehicle_detail['customer_code'] = $vehicle_inward->customer_code;
                $vehicle_detail['customer_name'] = $vehicle_inward->customer_name;
                $vehicle_detail['gate_in_number'] = $vehicle_inward->gate_in_number;
                $vehicle_detail['gate_in_date'] = $vehicle_inward->gate_in_date;
                $vehicle_detail['gate_out_date'] = $vehicle_inward->gate_out_date;
                $vehicle_detail['customer_name'] = $vehicle_inward->customer_name;
                $vehicle_detail['reg_number'] = $vehicle_inward->registration_number;
                $vehicle_detail['chassis_number'] = $vehicle_inward->chassis_number;
                $vehicle_detail['engine_number'] = $vehicle_inward->engine_number;
                $vehicle_detail['vehicle_status'] = $vehicle_inward->vehicle_delivery_status;
                // $vehicle_detail['service_completed'] = $vehicle_inward->inward_cancel_reason_id ? 'No' : 'Yes';
                // if( $vehicle_inward->inward_cancel_reason_id){
                // $vehicle_detail['billing_type'] = '';
                // }else{
                if ($vehicle_inward->billing_type) {
                    $vehicle_detail['service_completed'] = 'Yes';
                } else {
                    $vehicle_detail['service_completed'] = 'No';
                }
                $vehicle_detail['billing_type'] = $vehicle_inward->billing_type ? $vehicle_inward->billing_type : '-';
                $vehicle_detail['job_card_number'] = $vehicle_inward->job_card_number;
                // }

                if ($vehicle_inward->inward_cancel_reason_id) {
                    $vehicle_detail['invoice_date'] = '-';
                    $vehicle_detail['labour_inv_number'] = '-';
                    $vehicle_detail['labour_amount'] = '';
                    $vehicle_detail['labour_discount_amount'] = '';
                    $vehicle_detail['parts_inv_number'] = '-';
                    $vehicle_detail['parts_amount'] = '';
                    $vehicle_detail['part_discount_amount'] = '';
                } else {
                    // dump($vehicle_inward->manualDeliveryLabourInvoice);
                    if ($vehicle_inward->manualDeliveryLabourInvoice) {
                        $vehicle_detail['invoice_date'] = $vehicle_inward->manualDeliveryLabourInvoice->invoice_date;
                        $vehicle_detail['labour_inv_number'] = $vehicle_inward->manualDeliveryLabourInvoice->number;
                        $vehicle_detail['labour_amount'] = $vehicle_inward->manualDeliveryLabourInvoice->amount;
                        $vehicle_detail['labour_discount_amount'] = $vehicle_inward->labour_discount_amount;
                    } else {
                        $vehicle_detail['invoice_date'] = '-';
                        $vehicle_detail['labour_inv_number'] = '-';
                        $vehicle_detail['labour_amount'] = '';
                        $vehicle_detail['labour_discount_amount'] = '';
                    }
                    if ($vehicle_inward->manualDeliveryPartsInvoice) {
                        $vehicle_detail['parts_inv_number'] = $vehicle_inward->manualDeliveryPartsInvoice->number;
                        $vehicle_detail['parts_amount'] = $vehicle_inward->manualDeliveryPartsInvoice->amount;
                        $vehicle_detail['part_discount_amount'] = $vehicle_inward->part_discount_amount;
                    } else {
                        $vehicle_detail['parts_inv_number'] = '-';
                        $vehicle_detail['parts_amount'] = '';
                        $vehicle_detail['part_discount_amount'] = '';
                    }
                }

                $vehicle_detail['inward_cancel_reason'] = $vehicle_inward->inward_cancel_reason_name ? $vehicle_inward->inward_cancel_reason_name : '-';
                $vehicle_detail['remarks'] = $vehicle_inward->inward_cancel_reason ? $vehicle_inward->inward_cancel_reason : $vehicle_inward->warranty_reason;

                $vehicle_details[] = $vehicle_detail;
                $count++;
            }
        }

        $time_stamp = date('Y_m_d_h_i_s');
        Excel::create('Vehicle Delivery - ' . $time_stamp, function ($excel) use ($header, $vehicle_details) {
            $excel->sheet('Summary', function ($sheet) use ($header, $vehicle_details) {
                $sheet->fromArray($vehicle_details, null, 'A1');
                $sheet->row(1, $header);
                $sheet->row(1, function ($row) {
                    $row->setBackground('#07c63a');
                });
            });
            $excel->setActiveSheetIndex(0);
        })->export('xlsx');
    }

    public function exportTvsOneDiscount(Request $request)
    {
        ob_end_clean();

        // ini_set('memory_limit', '50M');
        ini_set('max_execution_time', 0);

        // dd($request->all());

        if ($request->date) {
            $date_range = explode(' to ', $request->date);
            $start_date = date('Y-m-d', strtotime($date_range[0]));
            $start_date = $start_date . ' 00:00:00';

            $end_date = date('Y-m-d', strtotime($date_range[1]));
            $end_date = $end_date . ' 23:59:59';
        } else {
            $start_date = date('Y-m-01 00:00:00');
            $end_date = date('Y-m-t 23:59:59');
        }

        $vehicle_inward = JobOrder::with(
            ['manualDeliveryLabourInvoice',
                'manualDeliveryPartsInvoice',
                // 'amcMember',
                // 'amcMember.amcPolicy',
                // 'amcMember.amcCustomer',
                'aggregateCoupon',
            ])
            ->select('regions.code as region_code', 'states.code as state_code', 'customers.code as customer_code', 'customers.name as customer_name', 'gate_logs.number as gate_in_number',
                DB::raw('DATE_FORMAT(gate_logs.gate_in_date,"%d-%m-%Y %h:%i %p") as gate_in_date'),
                DB::raw('DATE_FORMAT(gate_logs.gate_out_date,"%d-%m-%Y %h:%i %p") as gate_out_date'),
                'vehicles.registration_number', 'vehicles.engine_number', 'vehicles.chassis_number', 'job_orders.inward_cancel_reason_id', 'billing_type.name as billing_type', 'job_orders.warranty_reason', 'inward_cancel.name as inward_cancel_reason_name', 'job_orders.inward_cancel_reason', 'job_orders.vehicle_payment_status', 'pending_reasons.name as pending_reason', 'jv_customers.code as jv_customer_code', 'jv_customers.name as jv_customer_name', 'job_orders.pending_remarks', 'users.ecode as user_code', 'users.name as user_name', 'job_orders.vehicle_delivery_request_remarks', 'job_orders.approved_remarks', 'job_orders.approved_date_time', 'outlets.code as outlet_code', 'outlets.name as outlet_name', 'outlets.ax_name', 'vehicle_delivery_statuses.name as vehicle_delivery_status', 'job_orders.id', 'job_orders.job_card_number', 'job_orders.labour_discount_amount', 'job_orders.part_discount_amount', 'tvs_one_approval_statuses.name as tvs_one_approval_status', 'job_orders.service_policy_id')
            ->join('gate_logs', 'gate_logs.job_order_id', 'job_orders.id')
            ->leftJoin('vehicles', 'job_orders.vehicle_id', 'vehicles.id')
            ->leftJoin('customers', 'customers.id', 'job_orders.customer_id')
            ->leftJoin('models', 'models.id', 'vehicles.model_id')
            ->join('amc_members', 'amc_members.vehicle_id', 'vehicles.id')
            ->join('amc_policies', 'amc_policies.id', 'amc_members.policy_id')
            ->leftJoin('vehicle_delivery_statuses', 'vehicle_delivery_statuses.id', 'job_orders.vehicle_delivery_status_id')
            ->leftJoin('configs as billing_type', 'billing_type.id', 'job_orders.billing_type_id')
            ->leftJoin('configs as inward_cancel', 'inward_cancel.id', 'job_orders.inward_cancel_reason_id')
            ->leftJoin('pending_reasons', 'pending_reasons.id', 'job_orders.pending_reason_id')
            ->leftJoin('users', 'users.id', 'job_orders.vehicle_delivery_requester_id')
            ->leftJoin('customers as jv_customers', 'jv_customers.id', 'job_orders.jv_customer_id')
        // ->join('configs', 'configs.id', 'job_orders.status_id')
            ->join('outlets', 'outlets.id', 'job_orders.outlet_id')
            ->join('states', 'states.id', 'outlets.state_id')
            ->join('regions', 'regions.state_id', 'states.id')
            ->join('tvs_one_approval_statuses', 'tvs_one_approval_statuses.id', 'job_orders.tvs_one_approval_status_id')
        // ->where('job_orders.pending_reason_id', 2)
            ->whereNotNull('job_orders.status_id')
            ->whereNotNull('service_policy_id')
            ->whereDate('gate_logs.gate_in_date', '>=', $start_date)
            ->whereDate('gate_logs.gate_in_date', '<=', $end_date)
            ->orderBy('gate_logs.gate_in_date', 'asc')
            ->groupBy('job_orders.id');
        // ->get();

        if (!Entrust::can('view-all-outlet-tvs-one-discount-request')) {
            if (Entrust::can('view-mapped-outlet-tvs-one-discount-request')) {
                $outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
                array_push($outlet_ids, Auth::user()->employee->outlet_id);
                $vehicle_inward = $vehicle_inward->whereIn('job_orders.outlet_id', $outlet_ids);
            } else {
                $vehicle_inward = $vehicle_inward->where('job_orders.outlet_id', Auth::user()->working_outlet_id);
            }
        }

        if ($request->export_status_id) {
            $vehicle_inward = $vehicle_inward->where('job_orders.tvs_one_approval_status_id', $request->export_status_id);
        } else {
            $vehicle_inward = $vehicle_inward->whereIn('job_orders.tvs_one_approval_status_id', [1, 2, 3]);
        }

        if ($request->export_membership_id) {
            $vehicle_inward = $vehicle_inward->where('amc_policies.id', $request->export_membership_id);
        }

        if ($request->export_customer_id) {
            $vehicle_inward = $vehicle_inward->where('job_orders.customer_id', $request->export_customer_id);
        }

        $vehicle_inwards = $vehicle_inward->get();
        $vehicle_details = array();

        $header = [
            // 'Sno',
            'State',
            'Region',
            'Outlet',
            'Customer Code',
            'Customer Name',
            'GateIn Number',
            'GateIn Date & Time',
            'GateOut Date & Time',
            'Registration Number',
            'Chassis Number',
            'Engine Number',
            'Vehicle Status',
            'TVS One Discount Status',
            'Service Completed',
            'Billing Type',
            'Job Card Number',
            'Invoice Date',
            'Labour Invoice Number',
            'Labour Amount',
            'Labour Discount Amount',
            'Parts Invoice Number',
            'Parts Amount',
            'Parts Discount Amount',
            'Aggregate Coupon Used',
            'Aggregate Coupon Balanced',
            'Inward Cancel Reason',
            'Remarks',
            // 'Payment Status',

        ];
        // dd(count($vehicle_inward));
        if (count($vehicle_inwards) > 0) {
            $count = 1;
            foreach ($vehicle_inwards as $key => $vehicle_inward) {
                // dd($vehicle_inward);
                $vehicle_detail = array();

                //Total Aggregate Coupons
                $total_coupons = AmcAggregateCoupon::join('amc_members', 'amc_members.amc_customer_id', 'amc_aggregate_coupons.amc_customer_id')->where('amc_members.id', $vehicle_inward->service_policy_id)->count();

                //Aggregate Coupon Used
                $used_coupons = AmcAggregateCoupon::where('job_order_id', $vehicle_inward->id)->count();

                // $vehicle_detail['sno'] = $count;
                $vehicle_detail['state'] = $vehicle_inward->state_code;
                $vehicle_detail['region'] = $vehicle_inward->region_code;
                $vehicle_detail['outlet'] = $vehicle_inward->outlet_code . ' / ' . ($vehicle_inward->ax_name ? $vehicle_inward->ax_name : $vehicle_inward->outlet_name);
                $vehicle_detail['customer_code'] = $vehicle_inward->customer_code;
                $vehicle_detail['customer_name'] = $vehicle_inward->customer_name;
                $vehicle_detail['gate_in_number'] = $vehicle_inward->gate_in_number;
                $vehicle_detail['gate_in_date'] = $vehicle_inward->gate_in_date;
                $vehicle_detail['gate_out_date'] = $vehicle_inward->gate_out_date;
                $vehicle_detail['customer_name'] = $vehicle_inward->customer_name;
                $vehicle_detail['reg_number'] = $vehicle_inward->registration_number;
                $vehicle_detail['chassis_number'] = $vehicle_inward->chassis_number;
                $vehicle_detail['engine_number'] = $vehicle_inward->engine_number;
                $vehicle_detail['vehicle_status'] = $vehicle_inward->vehicle_delivery_status;
                $vehicle_detail['discount_status'] = $vehicle_inward->tvs_one_approval_status ? $vehicle_inward->tvs_one_approval_status : 'Pending';
                // $vehicle_detail['service_completed'] = $vehicle_inward->inward_cancel_reason_id ? 'No' : 'Yes';
                // if( $vehicle_inward->inward_cancel_reason_id){
                // $vehicle_detail['billing_type'] = '';
                // }else{
                if ($vehicle_inward->billing_type) {
                    $vehicle_detail['service_completed'] = 'Yes';
                } else {
                    $vehicle_detail['service_completed'] = 'No';
                }
                $vehicle_detail['billing_type'] = $vehicle_inward->billing_type ? $vehicle_inward->billing_type : '-';
                $vehicle_detail['job_card_number'] = $vehicle_inward->job_card_number;
                // }

                if ($vehicle_inward->inward_cancel_reason_id) {
                    $vehicle_detail['invoice_date'] = '-';
                    $vehicle_detail['labour_inv_number'] = '-';
                    $vehicle_detail['labour_amount'] = '';
                    $vehicle_detail['labour_discount_amount'] = '';
                    $vehicle_detail['parts_inv_number'] = '-';
                    $vehicle_detail['parts_amount'] = '';
                    $vehicle_detail['part_discount_amount'] = '';
                } else {
                    // dump($vehicle_inward->manualDeliveryLabourInvoice);
                    if ($vehicle_inward->manualDeliveryLabourInvoice) {
                        $vehicle_detail['invoice_date'] = $vehicle_inward->manualDeliveryLabourInvoice->invoice_date;
                        $vehicle_detail['labour_inv_number'] = $vehicle_inward->manualDeliveryLabourInvoice->number;
                        $vehicle_detail['labour_amount'] = $vehicle_inward->manualDeliveryLabourInvoice->amount;
                        $vehicle_detail['labour_discount_amount'] = $vehicle_inward->labour_discount_amount;
                    } else {
                        $vehicle_detail['invoice_date'] = '-';
                        $vehicle_detail['labour_inv_number'] = '-';
                        $vehicle_detail['labour_amount'] = '';
                        $vehicle_detail['labour_discount_amount'] = '';
                    }
                    if ($vehicle_inward->manualDeliveryPartsInvoice) {
                        $vehicle_detail['parts_inv_number'] = $vehicle_inward->manualDeliveryPartsInvoice->number;
                        $vehicle_detail['parts_amount'] = $vehicle_inward->manualDeliveryPartsInvoice->amount;
                        $vehicle_detail['part_discount_amount'] = $vehicle_inward->part_discount_amount;
                    } else {
                        $vehicle_detail['parts_inv_number'] = '-';
                        $vehicle_detail['parts_amount'] = '';
                        $vehicle_detail['part_discount_amount'] = '';
                    }
                }

                if ($vehicle_inward->service_policy_id) {
                    $vehicle_detail['used_coupon'] = $used_coupons > 0 ? $used_coupons : '0';
                    $remaining_coupon = $total_coupons - $used_coupons;
                    $vehicle_detail['remaining_coupon'] = $remaining_coupon > 0 ? $remaining_coupon : '0';
                } else {
                    $vehicle_detail['used_coupon'] = '0';
                    $vehicle_detail['remaining_coupon'] = '0';
                }

                $vehicle_detail['inward_cancel_reason'] = $vehicle_inward->inward_cancel_reason_name ? $vehicle_inward->inward_cancel_reason_name : '-';
                $vehicle_detail['remarks'] = $vehicle_inward->inward_cancel_reason ? $vehicle_inward->inward_cancel_reason : $vehicle_inward->warranty_reason;

                $vehicle_details[] = $vehicle_detail;
                $count++;
            }
        }

        $time_stamp = date('Y_m_d_h_i_s');
        Excel::create('Vehicle Delivery - ' . $time_stamp, function ($excel) use ($header, $vehicle_details) {
            $excel->sheet('Summary', function ($sheet) use ($header, $vehicle_details) {
                $sheet->fromArray($vehicle_details, null, 'A1');
                $sheet->row(1, $header);
                $sheet->row(1, function ($row) {
                    $row->setBackground('#07c63a');
                });
            });
            $excel->setActiveSheetIndex(0);
        })->export('xlsx');
    }
}
