<?php

namespace Abs\GigoPkg;

use App\BatteryLoadTestStatus;
use App\BatteryMake;
use App\Config;
use App\Http\Controllers\Controller;
use App\HydrometerElectrolyteStatus;
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
                'battery_load_test_statuses.name as overall_status', 'hydrometer_electrolyte_status_id', 'load_test_status_id',
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
            ->editColumn('load_test_status', function ($battery_list) {
                $status = 'yellow';
                if ($battery_list->load_test_status_id == 1) {
                    $status = 'green';
                } elseif ($battery_list->load_test_status_id == 3) {
                    $status = 'red';
                }
                return '<span class="text-' . $status . '">' . $battery_list->load_test_status . '</span>';
            })
            ->editColumn('hydrometer_electrolyte_status', function ($battery_list) {
                $status = 'yellow';
                if ($battery_list->hydrometer_electrolyte_status_id == 1) {
                    $status = 'green';
                } elseif ($battery_list->hydrometer_electrolyte_status_id == 3) {
                    $status = 'red';
                }
                return '<span class="text-' . $status . '">' . $battery_list->hydrometer_electrolyte_status . '</span>';
            })
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
        // dd($request->all());
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        // dd($request->all());
        if ($request->export_date) {
            $date_range = explode(' to ', $request->export_date);
            $start_date = date('Y-m-d', strtotime($date_range[0]));
            $start_date = $start_date . ' 00:00:00';

            $end_date = date('Y-m-d', strtotime($date_range[1]));
            $end_date = $end_date . ' 23:59:59';
        } else {
            $start_date = date('Y-m-01 00:00:00');
            $end_date = date('Y-m-t 23:59:59');
        }

        $battery_load_tests = BatteryLoadTestResult::select([
            'outlets.name as outlet_name', 'outlets.code as outlet_code', 'outlets.ax_name',
            DB::raw('DATE_FORMAT(battery_load_test_results.created_at,"%d-%m-%Y") as date'),
            'vehicles.registration_number as regis_number',
            'vehicles.chassis_number',
            'vehicles.engine_number',
            'vehicles.km_reading_type_id',
            'vehicles.km_reading',
            'vehicles.hr_reading',
            DB::raw('DATE_FORMAT(vehicles.sold_date,"%d-%m-%Y") as sold_date'),
            'customers.code as customer_code',
            'customers.name as customer_name',
            'battery_makes.name as battery_make',
            DB::raw('DATE_FORMAT(vehicle_batteries.manufactured_date,"%b") as manufactured_month'),
            DB::raw('DATE_FORMAT(vehicle_batteries.manufactured_date,"%Y") as manufactured_year'),
            'battery_load_test_results.amp_hour',
            'battery_load_test_results.battery_voltage',
            'load_test_statuses.name as load_test_status',
            'hydrometer_electrolyte_statuses.name as hydro_test_status',
            'battery_load_test_statuses.name as overall_status',
            'battery_load_test_results.remarks',
        ])
            ->join('outlets', 'outlets.id', 'battery_load_test_results.outlet_id')
            ->join('vehicle_batteries', 'vehicle_batteries.id', 'battery_load_test_results.vehicle_battery_id')
            ->join('load_test_statuses', 'load_test_statuses.id', 'battery_load_test_results.load_test_status_id')
            ->join('hydrometer_electrolyte_statuses', 'hydrometer_electrolyte_statuses.id', 'battery_load_test_results.hydrometer_electrolyte_status_id')
            ->join('battery_load_test_statuses', 'battery_load_test_statuses.id', 'battery_load_test_results.overall_status_id')
            ->join('vehicles', 'vehicles.id', 'vehicle_batteries.vehicle_id')
            ->join('customers', 'customers.id', 'vehicle_batteries.customer_id')
            ->join('battery_makes', 'battery_makes.id', 'vehicle_batteries.battery_make_id')
            ->whereDate('battery_load_test_results.created_at', '>=', $start_date)
            ->whereDate('battery_load_test_results.created_at', '<=', $end_date);

        if ($request->export_customer_id && $request->export_customer_id != '<%$ctrl.export_customer_id%>') {
            $battery_load_tests->where('vehicle_batteries.customer_id', $request->export_customer_id);
        }

        if ($request->export_battery_make_id && $request->export_battery_make_id != '<%$ctrl.export_battery_make_id%>') {
            $battery_load_tests->where('vehicle_batteries.battery_make_id', $request->export_battery_make_id);
        }

        if ($request->export_load_test_status_id && $request->export_load_test_status_id != '<%$ctrl.export_load_test_status_id%>') {
            $battery_load_tests->where('battery_load_test_results.load_test_status_id', $request->export_load_test_status_id);
        }

        if ($request->export_hydro_status_id && $request->export_hydro_status_id != '<%$ctrl.export_hydro_status_id%>') {
            $battery_load_tests->where('battery_load_test_results.hydrometer_electrolyte_status_id', $request->export_hydro_status_id);
        }

        if ($request->export_overall_status_id && $request->export_overall_status_id != '<%$ctrl.export_overall_status_id%>') {
            $battery_load_tests->where('battery_load_test_results.overall_status_id', $request->export_overall_status_id);
        }

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

        // dd($battery_load_tests);

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

        if (count($battery_load_tests) > 0) {
            $count = 1;
            foreach ($battery_load_tests as $key => $battery_load_test) {
                $battery_test_detail = array();

                // $battery_test_detail['sno'] = $count;
                $battery_test_detail['outlet'] = $battery_load_test->outlet_code . ' / ' . ($battery_load_test->ax_name ? $battery_load_test->ax_name : $battery_load_test->outlet_name);
                $battery_test_detail['date'] = $battery_load_test->date;
                $battery_test_detail['reg_number'] = $battery_load_test->regis_number;
                $battery_test_detail['chassis_number'] = $battery_load_test->chassis_number;
                $battery_test_detail['engine_number'] = $battery_load_test->engine_number;

                if ($battery_load_test->km_reading_type_id == '8040') {
                    $battery_test_detail['km_reading_type'] = 'KM';
                    $battery_test_detail['reading_value'] = $battery_load_test->km_reading;
                } else {
                    $battery_test_detail['km_reading_type'] = 'HRS';
                    $battery_test_detail['reading_value'] = $battery_load_test->hr_reading;
                }
                $battery_test_detail['date_of_sale'] = $battery_load_test->sold_date;
                $battery_test_detail['customer_code'] = $battery_load_test->customer_code;
                $battery_test_detail['customer_name'] = $battery_load_test->customer_name;

                $battery_test_detail['battery_make'] = $battery_load_test->battery_make;
                $battery_test_detail['manufactured_month'] = $battery_load_test->manufactured_month;
                $battery_test_detail['manufactured_year'] = $battery_load_test->manufactured_year;
                $battery_test_detail['amp_hour'] = $battery_load_test->amp_hour;
                $battery_test_detail['battery_voltage'] = $battery_load_test->battery_voltage;
                $battery_test_detail['load_test'] = $battery_load_test->load_test_status;
                $battery_test_detail['hydro_test'] = $battery_load_test->hydro_test_status;
                $battery_test_detail['overall_status'] = $battery_load_test->overall_status;
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

    public function exportOld(Request $request)
    {
        ob_end_clean();

        // ini_set('memory_limit', '50M');
        ini_set('max_execution_time', 0);

        // dd($request->all());
        if ($request->export_date) {
            $date_range = explode(' to ', $request->export_date);
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
}
