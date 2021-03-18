<?php

namespace Abs\GigoPkg;

use App\Config;
use App\Customer;
use App\Http\Controllers\Controller;
use App\JobOrder;
use App\VehicleDeliveryStatus;
use App\VehicleModel;
use Auth;
use DB;
use Entrust;
use Excel;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class OnSiteVisitController extends Controller
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
        ];
        return response()->json($this->data);
    }

    public function getOnSiteVisitList(Request $request)
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

        $vehicle_inwards = OnSiteOrder::join('customers', 'customers.id', 'on_site_orders.customer_id')
            ->join('outlets', 'outlets.id', 'on_site_orders.outlet_id')
            ->leftjoin('on_site_order_statuses', 'on_site_order_statuses.id', 'on_site_orders.status_id')
            ->select(
                'on_site_orders.id',
                'on_site_orders.number',
                'on_site_orders.status_id',
                DB::raw('DATE_FORMAT(on_site_orders.planned_visit_date,"%d-%m-%Y") as date'),
                'outlets.code as outlet_code',
                'customers.name as customer_name',
                'on_site_order_statuses.name as status'
            )
            // ->where(function ($query) use ($start_date, $end_date) {
            //     $query->whereDate('gate_logs.gate_in_date', '>=', $start_date)
            //         ->whereDate('gate_logs.gate_in_date', '<=', $end_date);
            // })
            // ->where(function ($query) use ($request) {
            //     if (!empty($request->reg_no)) {
            //         $query->where('vehicles.registration_number', 'LIKE', '%' . $request->reg_no . '%');
            //     }
            // })
            // ->where(function ($query) use ($request) {
            //     if (!empty($request->membership)) {
            //         $query->where('amc_policies.name', 'LIKE', '%' . $request->membership . '%');
            //     }
            // })
            // ->where(function ($query) use ($request) {
            //     if (!empty($request->gate_in_no)) {
            //         $query->where('gate_logs.number', 'LIKE', '%' . $request->gate_in_no . '%');
            //     }
            // })
            // ->where(function ($query) use ($request) {
            //     if ($request->registration_type == '1' || $request->registration_type == '0') {
            //         $query->where('vehicles.is_registered', $request->registration_type);
            //     }
            // })
            // ->where(function ($query) use ($request) {
            //     if (!empty($request->customer_id)) {
            //         $query->where('vehicle_owners.customer_id', $request->customer_id);
            //     }
            // })
            // ->where(function ($query) use ($request) {
            //     if (!empty($request->model_id)) {
            //         $query->where('vehicles.model_id', $request->model_id);
            //     }
            // })
            // ->where(function ($query) use ($request) {
            //     if (!empty($request->status_id)) {
            //         $query->where('job_orders.status_id', $request->status_id);
            //     }
            // })
            ->where('on_site_orders.company_id', Auth::user()->company_id)
        ;

        // if ($request->date_range) {
        //     $vehicle_inwards->whereDate('gate_logs.gate_in_date', '>=', $start_date)->whereDate('gate_logs.gate_in_date', '<=', $end_date);
        // }

        if (!Entrust::can('view-all-site-visit')) {
            if (Entrust::can('view-mapped-site-visit')) {
                $outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
                array_push($outlet_ids, Auth::user()->employee->outlet_id);
                $vehicle_inwards->whereIn('on_site_orders.outlet_id', $outlet_ids);
            } else {
                $vehicle_inwards->where('on_site_orders.outlet_id', Auth::user()->working_outlet_id);
            }
        }

        if (Entrust::can('parts-view-site-visit')) {
            $vehicle_inwards->whereIn('on_site_orders.status_id', [4]);
        }

        $vehicle_inwards->orderBy('on_site_orders.planned_visit_date', 'DESC');

        return Datatables::of($vehicle_inwards)
            ->rawColumns(['status', 'action'])
            
            ->editColumn('status', function ($vehicle_inward) {
                $status = $vehicle_inward->status_id == '8460' || $vehicle_inward->status_id == '8469' || $vehicle_inward->status_id == '8477' || $vehicle_inward->status_id == '8479' ? 'green' : 'blue';
                return '<span class="text-' . $status . '">' . $vehicle_inward->status . '</span>';
            })

            ->addColumn('action', function ($vehicle_inward) {
                $view_img = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
                $edit_img = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');

                $status_img = asset('public/theme/img/table/add-new-invoice.svg');
                $status_img_hover = asset('public/theme/img/table/add-hover.svg');

                $output = '';
                // dd($vehicle_inward->status_id,Entrust::can('edit-site-visit'));
                if ($vehicle_inward->status_id == 1 && Entrust::can('edit-site-visit')){
                    $output .= '<a href="#!/on-site-visit/form/' . $vehicle_inward->id . '" id = "" title="Form"><img src="' . $edit_img . '" alt="View" class="img-responsive" onmouseover=this.src="' . $edit_img . '" onmouseout=this.src="' . $edit_img . '"></a>';
                }

                $output .= '<a href="#!/on-site-visit/view/' . $vehicle_inward->id . '" id = "" title="View"><img src="' . $view_img . '" alt="View" class="img-responsive" onmouseover=this.src="' . $view_img . '" onmouseout=this.src="' . $view_img . '"></a>';
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
		'manualDeliveryPartsInvoice'])->select('regions.code as region_code', 'states.code as state_code', 'customers.code as customer_code', 'customers.name as customer_name', 'gate_logs.number as gate_in_number', 'gate_logs.gate_in_date', 'gate_logs.gate_out_date', 'vehicles.registration_number', 'vehicles.engine_number', 'vehicles.chassis_number', 'job_orders.inward_cancel_reason_id', 'billing_type.name as billing_type', 'job_orders.warranty_reason', 'inward_cancel.name as inward_cancel_reason_name', 'job_orders.inward_cancel_reason', 'job_orders.vehicle_payment_status', 'pending_reasons.name as pending_reason', 'jv_customers.code as jv_customer_code', 'jv_customers.name as jv_customer_name', 'job_orders.pending_remarks', 'users.ecode as user_code', 'users.name as user_name', 'job_orders.vehicle_delivery_request_remarks', 'job_orders.approved_remarks', 'job_orders.approved_date_time', 'outlets.code as outlet_code', 'outlets.name as outlet_name', 'outlets.ax_name','vehicle_delivery_statuses.name as vehicle_delivery_status','job_orders.id')
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
            ->join('configs', 'configs.id', 'job_orders.status_id')
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

        if($request->status_id){
            $vehicle_inward = $vehicle_inward->where('job_orders.status_id',$request->status_id);
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
			'Invoice Date',
			'Labour Invoice Number',
			'Labour Amount',
			'Parts Invoice Number',
			'Parts Amount',
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
                if($vehicle_inward->billing_type){
                    $vehicle_detail['service_completed'] = 'Yes';
                }else{
                    $vehicle_detail['service_completed'] = 'No';
                }
                $vehicle_detail['billing_type'] = $vehicle_inward->billing_type ? $vehicle_inward->billing_type : '-';
                // }
				
				if( $vehicle_inward->inward_cancel_reason_id){
					$vehicle_detail['invoice_date'] = '-';
					$vehicle_detail['labour_inv_number'] = '-';
					$vehicle_detail['labour_amount'] = '';
					$vehicle_detail['parts_inv_number'] = '-';
					$vehicle_detail['parts_amount'] = '';
				}else{
					// dump($vehicle_inward->manualDeliveryLabourInvoice);
					if($vehicle_inward->manualDeliveryLabourInvoice){
						$vehicle_detail['invoice_date'] = $vehicle_inward->manualDeliveryLabourInvoice->invoice_date;
						$vehicle_detail['labour_inv_number'] = $vehicle_inward->manualDeliveryLabourInvoice->number;
						$vehicle_detail['labour_amount'] = $vehicle_inward->manualDeliveryLabourInvoice->amount;
					}else{
						$vehicle_detail['invoice_date'] = '-';
						$vehicle_detail['labour_inv_number'] = '-';
						$vehicle_detail['labour_amount'] = '';
					}
					if($vehicle_inward->manualDeliveryPartsInvoice){
						$vehicle_detail['parts_inv_number'] = $vehicle_inward->manualDeliveryPartsInvoice->number;
						$vehicle_detail['parts_amount'] = $vehicle_inward->manualDeliveryPartsInvoice->amount;
					}else{
						$vehicle_detail['parts_inv_number'] = '-';
						$vehicle_detail['parts_amount'] = '';
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
				$sheet->fromArray($vehicle_details, NULL, 'A1');
				$sheet->row(1, $header);
				$sheet->row(1, function ($row) {
					$row->setBackground('#07c63a');
				});
			});
			$excel->setActiveSheetIndex(0);
		})->export('xlsx');
    }
}
