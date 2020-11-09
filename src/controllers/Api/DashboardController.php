<?php

namespace Abs\GigoPkg\Api;

use App\AttendanceLog;
use App\Employee;
use App\Entity;
use App\GateLog;
use App\GatePass;
use App\Http\Controllers\Controller;
use App\JobCard;
use App\JobOrder;
use App\JobOrderPart;
use App\JobOrderRepairOrder;
use App\Outlet;
use App\SplitOrderType;
use App\State;
use App\Survey;
use App\User;
use App\Vehicle;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;

class DashboardController extends Controller {
	public $successStatus = 200;

	public function getOutletData($state_id = NULL) {

		if (Entrust::can('dashboard-view-all-outlet')) {
			$outlet_list = collect(Outlet::where('company_id', Auth::user()->company_id)->where('state_id', $state_id)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Outlet']);
		} else {
			if (Entrust::can('dashboard-view-mapped-outlet')) {
				$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
				array_push($outlet_ids, Auth::user()->employee->outlet_id);

				$outlet_list = collect(Outlet::whereIn('id', $outlet_ids)->where('state_id', $state_id)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Outlet']);
			} else {
				$outlet_list = collect(Outlet::where('id', Auth::user()->employee->outlet_id)->where('state_id', $state_id)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Outlet']);
			}
		}

		$this->data['outlet_list'] = $outlet_list;
		return response()->json($this->data);
	}

	public function getDashboard11(Request $request) {
		// dd($request->all());

		if ($request->date_range && $request->date_range != '<%$ctrl.date_range%>') {
			$date_range = explode(' to ', $request->date_range);
			$start_date = date('Y-m-d', strtotime($date_range[0]));
			$start_date = $start_date . ' 00:00:00';

			$end_date = date('Y-m-d', strtotime($date_range[1]));
			$end_date = $end_date . ' 23:59:59';
		} else {
			$start_date = date('Y-m-d 00:00:00');
			// $start_date = date('Y-m-d 00:00:00', strtotime("-1 days")); //Previous day
			// $end_date = date('Y-m-t 23:59:59');
			$end_date = date('Y-m-d 23:59:59');
		}

		if ($request->state_id) {
			if ($request->outlet_id) {
				$outlet_ids[] = $request->outlet_id;
			} else {
				$outlet_ids = Outlet::where('state_id', $request->state_id)->where('company_id', Auth::user()->company_id)->pluck('id')->toArray();
			}
		} else {
			if (Entrust::can('dashboard-view-all-outlet')) {
				$outlet_list = Outlet::where('company_id', Auth::user()->company_id)->pluck('id')->toArray();
			} else {
				if (Entrust::can('dashboard-view-mapped-outlet')) {
					$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
					array_push($outlet_ids, Auth::user()->employee->outlet_id);

					$outlet_list = Outlet::whereIn('id', $outlet_ids)->pluck('id')->toArray();
				} else {

					$outlet_list = Outlet::where('id', Auth::user()->employee->outlet_id)->pluck('id')->toArray();
				}
			}

			$outlet_ids = $outlet_list;
		}

		// dd($outlet_ids);

		$customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		//Total Mechanic
		$total_employees = Employee::where('is_mechanic', 1)->count();

		//Total Present Employees
		$present_employees = AttendanceLog::
			// join('users', 'users.id', 'attendance_logs.user_id')
			// ->join('employees', 'employees.id', 'users.entity_id')
			// ->where('users.user_type_id', 1)
			whereDate('attendance_logs.created_at', '>=', $start_date)
			->whereDate('attendance_logs.created_at', '<=', $end_date)
			->groupBy('attendance_logs.user_id', 'attendance_logs.date')
			->get();

		// dd(count($present_employees));
		//Total Absent Employees

		//Total Kanban Employees

		//Total GateIn
		$gate_in_vehicles = GateLog::join('job_orders', 'job_orders.id', 'gate_logs.job_order_id')->whereDate('gate_logs.gate_in_date', '>=', $start_date)->whereDate('gate_logs.gate_in_date', '<=', $end_date)->whereIn('gate_logs.outlet_id', $outlet_ids)->pluck('job_orders.vehicle_id')->toArray();
		// SELECT *  FROM `gate_logs` WHERE `gate_in_date` >= '2020-06-01' AND `gate_in_date` <= '2020-11-21' ORDER BY `id`  DESC

		$total_gate_in_vehicles = count($gate_in_vehicles);
		$total_registered_vehicles = Vehicle::whereIn('id', $gate_in_vehicles)->where('is_registered', 1)->count();
		$total_unregistered_vehicles = Vehicle::whereIn('id', $gate_in_vehicles)->where('is_registered', 0)->count();

		$gate_in_data['total_vehicles'] = $total_gate_in_vehicles;
		$gate_in_data['total_registered_vehicles'] = $total_registered_vehicles;
		$gate_in_data['total_unregistered_vehicles'] = $total_unregistered_vehicles;

		$dashboard_data['gate_in_data'] = $gate_in_data;

		//Total Inward Inprogress Vehicles
		$inward_inprogress_vehicles = JobOrder::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->whereIn('status_id', [8463, 8460, 8469, 8471, 8472, 8474, 8473])->whereIn('outlet_id', $outlet_ids)->pluck('id')->toArray();
		// SELECT *  FROM `job_orders` WHERE `created_at` >= '2020-06-01' AND `created_at` <= '2020-11-21' ORDER BY `id`  DESC

		$total_inward_inprogress_vehicles = count($inward_inprogress_vehicles);

		$inward_inprogress_repair_order = JobOrderRepairOrder::whereIn('job_order_id', $inward_inprogress_vehicles)
			->whereNull('removal_reason_id')
			->whereIn('split_order_type_id', $customer_paid_type_id)
			->sum('amount');

		$inward_inprogress_parts = JobOrderPart::whereIn('job_order_id', $inward_inprogress_vehicles)
			->whereNull('removal_reason_id')
			->whereIn('split_order_type_id', $customer_paid_type_id)
			->sum('amount');

		$total_inward_inprogress_value = $inward_inprogress_repair_order + $inward_inprogress_parts;

		$inward_inprogress_data['total_vehicles'] = $total_inward_inprogress_vehicles;
		$inward_inprogress_data['repair_order_amount'] = number_format($inward_inprogress_repair_order, 2);
		$inward_inprogress_data['parts_amount'] = number_format($inward_inprogress_parts, 2);
		$inward_inprogress_data['total_amount'] = number_format($total_inward_inprogress_value, 2);

		$dashboard_data['inward_inprogress_data'] = $inward_inprogress_data;

		//Total Inward Completed Vehicles
		$inward_vehicles = JobOrder::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->whereIn('status_id', [8470, 8476, 8461])->whereIn('outlet_id', $outlet_ids)->pluck('id')->toArray();
		// SELECT *  FROM `job_orders` WHERE `created_at` >= '2020-06-01' AND `created_at` <= '2020-11-21' ORDER BY `id`  DESC

		$total_inward_vehicles = count($inward_vehicles);

		$inward_repair_order = JobOrderRepairOrder::whereIn('job_order_id', $inward_vehicles)
			->whereNull('removal_reason_id')
			->whereIn('split_order_type_id', $customer_paid_type_id)
			->sum('amount');

		$inward_parts = JobOrderPart::whereIn('job_order_id', $inward_vehicles)
			->whereNull('removal_reason_id')
			->whereIn('split_order_type_id', $customer_paid_type_id)
			->sum('amount');

		$total_inward_value = $inward_repair_order + $inward_parts;

		$inward_data['total_vehicles'] = $total_inward_vehicles;
		$inward_data['repair_order_amount'] = number_format($inward_repair_order, 2);
		$inward_data['parts_amount'] = number_format($inward_parts, 2);
		$inward_data['total_amount'] = number_format($total_inward_value, 2);

		$dashboard_data['inward_data'] = $inward_data;

		//Total Work Inprogress Vehicles
		$wip_vehicles = JobCard::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->whereIn('status_id', [8221, 8222])->whereIn('outlet_id', $outlet_ids)->pluck('job_order_id')->toArray();

		$total_wip_vehicles = count($wip_vehicles);

		$wip_repair_order = JobOrderRepairOrder::whereIn('job_order_id', $wip_vehicles)
			->whereNull('removal_reason_id')
			->whereIn('split_order_type_id', $customer_paid_type_id)
			->sum('amount');

		$wip_parts = JobOrderPart::whereIn('job_order_id', $wip_vehicles)
			->whereNull('removal_reason_id')
			->whereIn('split_order_type_id', $customer_paid_type_id)
			->sum('amount');

		$total_wip_value = $wip_repair_order + $wip_parts;

		$wip_data['total_vehicles'] = $total_wip_vehicles;
		$wip_data['repair_order_amount'] = number_format($wip_repair_order, 2);
		$wip_data['parts_amount'] = number_format($wip_parts, 2);
		$wip_data['total_amount'] = number_format($total_wip_value, 2);

		$dashboard_data['wip_data'] = $wip_data;

		//Total Work Completed Vehicles
		$wc_vehicles = JobCard::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->whereIn('outlet_id', $outlet_ids)->whereIn('status_id', [8223, 8224, 8225, 8226, 8227, 8228, 8231])->pluck('job_order_id')->toArray();

		$total_wc_vehicles = count($wc_vehicles);

		$wc_repair_order = JobOrderRepairOrder::whereIn('job_order_id', $wc_vehicles)
			->whereNull('removal_reason_id')
			->whereIn('split_order_type_id', $customer_paid_type_id)
			->sum('amount');

		$wc_parts = JobOrderPart::whereIn('job_order_id', $wc_vehicles)
			->whereNull('removal_reason_id')
			->whereIn('split_order_type_id', $customer_paid_type_id)
			->sum('amount');

		$total_wc_value = $wc_repair_order + $wc_parts;

		$wc_data['total_vehicles'] = $total_wc_vehicles;
		$wc_data['repair_order_amount'] = number_format($wc_repair_order, 2);
		$wc_data['parts_amount'] = number_format($wc_parts, 2);
		$wc_data['total_amount'] = number_format($total_wc_value, 2);

		$dashboard_data['wc_data'] = $wc_data;

		//Total Gate Out Vehicles
		$gate_out_vehicles = GatePass::join('job_orders', 'job_orders.id', 'gate_passes.job_order_id')->whereIn('job_orders.outlet_id', $outlet_ids)->where('gate_passes.status_id', 8341)->whereDate('gate_passes.created_at', '>=', $start_date)->whereDate('gate_passes.created_at', '<=', $end_date)->where('gate_passes.type_id', 8280)->pluck('gate_passes.job_order_id')->toArray();

		$total_gate_out_vehicles = count($gate_out_vehicles);

		$gate_out_repair_order = JobOrderRepairOrder::whereIn('job_order_id', $gate_out_vehicles)
			->whereNull('removal_reason_id')
			->whereIn('split_order_type_id', $customer_paid_type_id)
			->sum('amount');

		$gate_out_parts = JobOrderPart::whereIn('job_order_id', $gate_out_vehicles)
			->whereNull('removal_reason_id')
			->whereIn('split_order_type_id', $customer_paid_type_id)
			->sum('amount');

		$total_gate_out_value = $gate_out_repair_order + $gate_out_parts;

		$gate_out_data['total_vehicles'] = $total_gate_out_vehicles;
		$gate_out_data['repair_order_amount'] = number_format($gate_out_repair_order, 2);
		$gate_out_data['parts_amount'] = number_format($gate_out_parts, 2);
		$gate_out_data['total_amount'] = number_format($total_gate_out_value, 2);

		$dashboard_data['gate_out_data'] = $gate_out_data;

		//Total Feedback provide customers
		$feedback_provide_customers = Survey::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->where('attendee_id', 11201)->where('status_id', 11241)->count();

		$feedback_data['total_feedbacks'] = $feedback_provide_customers;

		$dashboard_data['feedback_data'] = $feedback_data;

		//Total OSL
		$total_osl = GatePass::join('job_cards', 'job_cards.id', 'gate_passes.job_card_id')->whereDate('gate_passes.created_at', '>=', $start_date)->whereDate('gate_passes.created_at', '<=', $end_date)->whereIn('job_cards.outlet_id', $outlet_ids)->where('gate_passes.type_id', 8281)->count();

		//Completed OSL
		$completed_osl = GatePass::join('job_cards', 'job_cards.id', 'gate_passes.job_card_id')->whereDate('gate_passes.created_at', '>=', $start_date)->whereDate('gate_passes.created_at', '<=', $end_date)->where('gate_passes.type_id', 8281)->whereIn('gate_passes.status_id', [8302, 8304])->count();

		//Pending OSL
		$pending_osl = GatePass::join('job_cards', 'job_cards.id', 'gate_passes.job_card_id')->whereDate('gate_passes.created_at', '>=', $start_date)->whereDate('gate_passes.created_at', '<=', $end_date)->where('gate_passes.type_id', 8281)->whereIn('gate_passes.status_id', [8300, 8301, 8303])->count();

		$osl_data['total_osl'] = $total_osl;
		$osl_data['completed_osl'] = $completed_osl;
		$osl_data['pending_osl'] = $pending_osl;

		$dashboard_data['osl_data'] = $osl_data;

		//Estimation Approved Inward
		$estimation_approved_vehicles = JobOrder::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->where('is_customer_approved', 1)->pluck('id')->toArray();

		$total_est_approved_vehicles = count($estimation_approved_vehicles);

		$est_approved_repair_order = JobOrderRepairOrder::whereIn('job_order_id', $estimation_approved_vehicles)
			->whereNull('removal_reason_id')
			->whereIn('split_order_type_id', $customer_paid_type_id)
			->sum('amount');

		$est_approved_parts = JobOrderPart::whereIn('job_order_id', $estimation_approved_vehicles)
			->whereNull('removal_reason_id')
			->whereIn('split_order_type_id', $customer_paid_type_id)
			->sum('amount');

		$total_est_approved_value = $est_approved_repair_order + $est_approved_parts;

		$est_approved_data['total_vehicles'] = $total_est_approved_vehicles;
		$est_approved_data['repair_order_amount'] = number_format($est_approved_repair_order, 2);
		$est_approved_data['parts_amount'] = number_format($est_approved_parts, 2);
		$est_approved_data['total_amount'] = number_format($total_est_approved_value, 2);

		$dashboard_data['est_approved_data'] = $est_approved_data;

		if (Entrust::can('dashboard-view-all-outlet')) {
			$state_list = collect(State::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select State']);
		} else {
			if (Entrust::can('dashboard-view-mapped-outlet')) {
				$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
				array_push($outlet_ids, Auth::user()->employee->outlet_id);

				$state_list = collect(State::join('outlets', 'outlets.state_id', 'states.id')->whereIn('outlets.id', $outlet_ids)->select('states.id', 'states.name')->groupBy('states.id')->get())->prepend(['id' => '', 'name' => 'Select State']);
			} else {
				$state_list = collect(State::join('outlets', 'outlets.state_id', 'states.id')->where('outlets.id', Auth::user()->employee->outlet_id)->select('states.id', 'states.name')->groupBy('states.id')->get())->prepend(['id' => '', 'name' => 'Select State']);
			}
		}

		$dashboard_data['state_list'] = $state_list;

		$dashboard_datas = $dashboard_data;

		// dd($dashboard_data);

		return response()->json(['success' => true, 'dashboard_data' => $dashboard_datas]);

	}

	public function getDashboard(Request $request) {
		// dd($request->all());

		$validator = Validator::make($request->all(), [
			'user_id' => [
				'required',
				'exists:users,id',
			],
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => $validator->errors()->all(),
			], $this->successStatus);
		}

		$getversion_code = DB::table('version_control')->where('project_name', 'GIGO')->orderBy('id', 'DESC')->first();
		if ($getversion_code != NULL) {
			$version_code = $getversion_code->version_code;
			$version_name = $getversion_code->version_name;
		} else {
			$version_code = 0;
			$version_name = 0;
		}

		$version_details['version_code'] = $version_code;
		$version_details['version_name'] = $version_name;

		//User Details
		$user = User::find($request->user_id);
		if ($user->user_type_id == 1) {
			//EMPLOYEE
			$user->employee;
			$user->employee->designation;
			$user->employee->outlet;
			$user->employee->reporting_name;
		}

		$user->entity;
		$user->permissions = $user->perms();

		$path = url('storage/app/public/users/profiles/');
		$path = $path . '/' . $user->id . '/' . $user->image;

		//Company
		$user->company;

		//Reporting Manager
		$user->reporting_manager;

		//Business
		$user->businesses;

		//Profile Image Path
		$user->path = $path;

		return response()->json(['success' => true, 'version_details' => $version_details, 'user' => $user]);

	}
}
