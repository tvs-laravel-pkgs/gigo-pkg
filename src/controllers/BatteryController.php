<?php

namespace Abs\GigoPkg;

use App\AmcAggregateCoupon;
use App\BatteryLoadTestStatus;
use App\BatteryMake;
use App\Config;
use App\Http\Controllers\Controller;
use App\HydrometerElectrolyteStatus;
use App\JobOrder;
use App\LoadTestStatus;
use Auth;
use DB;
use Entrust;
use Excel;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class BatteryController extends Controller
{

    public function __construct()
    {
        $this->data['theme'] = config('custom.theme');
    }

    public function getBatteryFilterData()
    {
        $extras = [
            'battery_list' => collect(BatteryMake::where('company_id', Auth::user()->company_id)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Battery']),
            'battery_load_test_status_list' => collect(BatteryLoadTestStatus::where('company_id', Auth::user()->company_id)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Status']),
            'load_test_result_status_list' => collect(LoadTestStatus::where('company_id', Auth::user()->company_id)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Status']),
            'hydrometer_status_list' => collect(HydrometerElectrolyteStatus::where('company_id', Auth::user()->company_id)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Status']),
        ];

        $this->data['extras'] = $extras;

        return response()->json($this->data);
    }

    public function getBatteryList(Request $request)
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

        $battery_list = BatteryLoadTestResult::join('vehicle_batteries', 'vehicle_batteries.id', 'battery_load_test_results.vehicle_battery_id')
            ->join('customers', 'customers.id', 'vehicle_batteries.customer_id')
            ->join('vehicles', 'vehicles.id', 'vehicle_batteries.vehicle_id')
            ->join('battery_makes', 'battery_makes.id', 'vehicle_batteries.battery_make_id')
            ->join('outlets', 'outlets.id', 'battery_load_test_results.outlet_id')
            ->join('load_test_statuses', 'load_test_statuses.id', 'battery_load_test_results.load_test_status_id')
            ->join('hydrometer_electrolyte_statuses', 'hydrometer_electrolyte_statuses.id', 'battery_load_test_results.hydrometer_electrolyte_status_id')
            ->join('battery_load_test_statuses', 'battery_load_test_statuses.id', 'battery_load_test_results.overall_status_id')
            ->select(
                'battery_load_test_results.id',
                'customers.name as customer_name',
                'battery_makes.name as battery_name',
                'vehicles.registration_number',
                'outlets.code as outlet_code',
                'load_test_statuses.name as load_test_status',
                'hydrometer_electrolyte_statuses.name as hydrometer_electrolyte_status',
                'battery_load_test_statuses.name as overall_status',
                DB::raw('DATE_FORMAT(battery_load_test_results.created_at,"%d/%m/%Y, %h:%i %p") as date')
            )

            ->where(function ($query) use ($request) {
                if (!empty($request->reg_no)) {
                    $query->where('vehicles.registration_number', 'LIKE', '%' . $request->reg_no . '%');
                }
            })

            ->where(function ($query) use ($request) {
                if (!empty($request->customer_id)) {
                    $query->where('vehicle_batteries.customer_id', $request->customer_id);
                }
            })

            ->where(function ($query) use ($request) {
                if (!empty($request->battery_make_id) && $request->battery_make_id != '<%$ctrl.battery_make_id%>') {
                    $query->where('vehicle_batteries.battery_make_id', $request->battery_make_id);
                }
            })

            ->where(function ($query) use ($request) {
                if (!empty($request->load_test_status_id) && $request->load_test_status_id != '<%$ctrl.load_test_status_id%>') {
                    $query->where('battery_load_test_results.load_test_status_id', $request->load_test_status_id);
                }
            })

            ->where(function ($query) use ($request) {
                if (!empty($request->hydro_status_id) && $request->hydro_status_id != '<%$ctrl.hydro_status_id%>') {
                    $query->where('battery_load_test_results.hydrometer_electrolyte_status_id', $request->hydro_status_id);
                }
            })

            ->where(function ($query) use ($request) {
                if (!empty($request->overall_status_id) && $request->overall_status_id != '<%$ctrl.overall_status_id%>') {
                    $query->where('battery_load_test_results.overall_status_id', $request->overall_status_id);
                }
            })

            ->where('battery_load_test_results.company_id', Auth::user()->company_id)
        // ->get()
        ;

        // dd($battery_list);
        if ($request->date_range) {
            $battery_list->whereDate('battery_load_test_results.created_at', '>=', $start_date)->whereDate('battery_load_test_results.created_at', '<=', $end_date);
        }

        if (!Entrust::can('view-all-outlet-battery-result')) {
            if (Entrust::can('view-mapped-outlet-battery-result')) {
                $outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
                array_push($outlet_ids, Auth::user()->employee->outlet_id);
                $battery_list->whereIn('battery_load_test_results.outlet_id', $outlet_ids);
            } else {
                $battery_list->where('battery_load_test_results.outlet_id', Auth::user()->working_outlet_id);
            }
        }

        $battery_list->orderBy('battery_load_test_results.created_at', 'DESC');

        return Datatables::of($battery_list)
        // ->editColumn('vehicle_status', function ($vehicle_inward) {
        //     $status = 'blue';
        //     if ($vehicle_inward->vehicle_delivery_status_id == 3) {
        //         $status = 'green';
        //     } elseif ($vehicle_inward->vehicle_delivery_status_id == 2 || $vehicle_inward->vehicle_delivery_status_id == 4) {
        //         $status = 'red';
        //     }
        //     return '<span class="text-' . $status . '">' . $vehicle_inward->vehicle_status . '</span>';
        // })
            ->addColumn('action', function ($battery_list) {
                $view_img = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
                $edit_img = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');

                $status_img = asset('public/theme/img/table/add-new-invoice.svg');
                $status_img_hover = asset('public/theme/img/table/add-hover.svg');

                $output = '';

                if (Entrust::can('edit-battery-result')) {
                    $output .= '<a href="#!/battery/form/' . $battery_list->id . '" id = "" title="Form"><img src="' . $edit_img . '" alt="View" class="img-responsive" onmouseover=this.src="' . $edit_img . '" onmouseout=this.src="' . $edit_img . '"></a>';
                }

                if (Entrust::can('view-battery-result')) {
                    $output .= '<a href="#!/battery/view/' . $battery_list->id . '" id = "" title="View"><img src="' . $view_img . '" alt="View" class="img-responsive" onmouseover=this.src="' . $view_img . '" onmouseout=this.src="' . $view_img . '"></a>';
                }

                return $output;
            })
            ->make(true);
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

        $battery_load_tests = BatteryLoadTestResult::with([
            'vehicleBattery',
            'vehicleBattery.batteryMake',
            'vehicleBattery.customer',
            'vehicleBattery.customer.address',
            'vehicleBattery.customer.address.country',
            'vehicleBattery.customer.address.state',
            'vehicleBattery.customer.address.city',
            'vehicleBattery.vehicle',
            'vehicleBattery.vehicle.model',
            'outlet',
            'batteryLoadTestStatus',
            'loadTestStatus',
            'hydrometerElectrolyteStatus',
        ])
            ->whereDate('battery_load_test_results.created_at', '>=', $start_date)
            ->whereDate('battery_load_test_results.created_at', '<=', $end_date)
        // ->get()
        ;

        if (!Entrust::can('view-all-outlet-battery-result')) {
            if (Entrust::can('view-mapped-outlet-battery-result')) {
                $outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
                array_push($outlet_ids, Auth::user()->employee->outlet_id);
                $battery_load_tests->whereIn('battery_load_test_results.outlet_id', $outlet_ids);
            } else {
                $battery_load_tests->where('battery_load_test_results.outlet_id', Auth::user()->working_outlet_id);
            }
        }

        $battery_load_tests = $battery_load_tests->get();

        $battery_test_details = array();

        $header = [
            // 'Sno',
            'Outlet',
            'Date',
            'Registration Number',
            'Chassis Number',
            'Engine Number',
            'KM Reading Type',
            'KM / HRS Reading',
            'Date of Sale',
            'Customer Code',
            'Customer Name',
            'Battery Make',
            'Manufactured Month',
            'Manufactured Year',
            'AMP Hour',
            'Volt',
            'Load Test',
            'Hydrometer Electrolyte',
            'Overall Status',
            'Remarks',
        ];
        // dd(count($battery_load_tests));
        if (count($battery_load_tests) > 0) {
            $count = 1;
            foreach ($battery_load_tests as $key => $battery_load_test) {
                // dd($battery_load_test);
                $battery_test_detail = array();

                // $battery_test_detail['sno'] = $count;
                $battery_test_detail['outlet'] = $battery_load_test->outlet->code . ' / ' . ($battery_load_test->outlet->ax_name ? $battery_load_test->outlet->ax_name : $battery_load_test->outlet->name);
                $battery_test_detail['date'] = date('d-m-Y', strtotime($battery_load_test->created_at));
                $battery_test_detail['reg_number'] = $battery_load_test->vehicleBattery->vehicle->registration_number;
                $battery_test_detail['chassis_number'] = $battery_load_test->vehicleBattery->vehicle->chassis_number;
                $battery_test_detail['engine_number'] = $battery_load_test->vehicleBattery->vehicle->engine_number;

                if ($battery_load_test->vehicleBattery->vehicle->km_reading_type_id == '8040') {
                    $battery_test_detail['km_reading_type'] = 'KM';
                    $battery_test_detail['reading_value'] = $battery_load_test->vehicleBattery->vehicle->km_reading;
                } else {
                    $battery_test_detail['km_reading_type'] = 'HRS';
                    $battery_test_detail['reading_value'] = $battery_load_test->vehicleBattery->vehicle->hr_reading;
                }
                $battery_test_detail['date_of_sale'] = $battery_load_test->vehicleBattery->vehicle->sold_date;
                $battery_test_detail['customer_code'] = $battery_load_test->vehicleBattery->customer->code;
                $battery_test_detail['customer_name'] = $battery_load_test->vehicleBattery->customer->name;

                $battery_test_detail['battery_make'] = $battery_load_test->vehicleBattery->batteryMake->name;
                $battery_test_detail['manufactured_month'] = date('F', strtotime($battery_load_test->vehicleBattery->manufactured_date));
                $battery_test_detail['manufactured_year'] = date('Y', strtotime($battery_load_test->vehicleBattery->manufactured_date));
                $battery_test_detail['amp_hour'] = $battery_load_test->amp_hour;
                $battery_test_detail['battery_voltage'] = $battery_load_test->battery_voltage;
                $battery_test_detail['load_test'] = $battery_load_test->loadTestStatus->name;
                $battery_test_detail['hydro_test'] = $battery_load_test->hydrometerElectrolyteStatus->name;
                $battery_test_detail['overall_status'] = $battery_load_test->batteryLoadTestStatus->name;
                $battery_test_detail['remarks'] = $battery_load_test->remarks;

                // dd($battery_test_detail);
                $battery_test_details[] = $battery_test_detail;
                $count++;
            }
        }

        $time_stamp = date('Y_m_d_h_i_s');
        Excel::create('Battery Details - ' . $time_stamp, function ($excel) use ($header, $battery_test_details) {
            $excel->sheet('Summary', function ($sheet) use ($header, $battery_test_details) {
                $sheet->fromArray($battery_test_details, null, 'A1');
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
