<?php

namespace Abs\GigoPkg;
use App\City;
use App\Config;
use App\Customer;
use App\Http\Controllers\Controller;
use App\JobOrder;
use App\Part;
use App\VehicleModel;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class VehicleInwardController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getVehicleInwardFilter() {
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
		];
		return response()->json($this->data);
	}

	public function getVehicleInwardList(Request $request) {
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
			->join('configs', 'configs.id', 'job_orders.status_id')
			->select(
				'job_orders.id',
				DB::raw('IF(vehicles.is_registered = 1,"Registered Vehicle","Un-Registered Vehicle") as registration_type'),
				'vehicles.registration_number',
				DB::raw('COALESCE(models.model_number, "-") as model_number'),
				'gate_logs.number',
				'job_orders.status_id',
				DB::raw('DATE_FORMAT(gate_logs.gate_in_date,"%d/%m/%Y, %h:%i %p") as date'),
				'job_orders.driver_name',
				'job_orders.driver_mobile_number as driver_mobile_number',
				'job_orders.is_customer_agreed',
				DB::raw('COALESCE(GROUP_CONCAT(amc_policies.name), "-") as amc_policies'),
				'configs.name as status',
				DB::raw('COALESCE(customers.name, "-") as customer_name')
			)
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

		if (!Entrust::can('view-overall-outlets-vehicle-inward')) {
			if (Entrust::can('view-mapped-outlet-vehicle-inward')) {
				$vehicle_inwards->whereIn('job_orders.outlet_id', Auth::user()->employee->outlets->pluck('id')->toArray());
			}
			if (Entrust::can('view-own-outlet-vehicle-inward')) {
				$vehicle_inwards->where('job_orders.outlet_id', Auth::user()->employee->outlet_id)->whereNull('job_orders.service_advisor_id')->whereNull('job_orders.floor_supervisor_id');
			}
			else
			{
				$vehicle_inwards->where('job_orders.service_advisor_id' ,Auth::user()->id )->whereNull('job_orders.floor_supervisor_id');
			}
			 /*else {
				$vehicle_inwards->where('job_orders.outlet_id', Auth::user()->employee->outlet_id)
					->whereRaw("IF (`job_orders`.`status_id` = '8460', `job_orders`.`service_advisor_id` IS  NULL, `job_orders`.`service_advisor_id` = '" . $request->service_advisor_id . "')");
			}*/
		}
		$vehicle_inwards->groupBy('job_orders.id');
		$vehicle_inwards->orderBy('job_orders.created_at', 'DESC');

		return Datatables::of($vehicle_inwards)
			->rawColumns(['status', 'action'])
			->filterColumn('registration_type', function ($query, $keyword) {
				$sql = 'IF(vehicles.is_registered = 1,"Registered Vehicle","Un-Registered Vehicle")  like ?';
				$query->whereRaw($sql, ["%{$keyword}%"]);
			})
			->editColumn('status', function ($vehicle_inward) {
				$status = $vehicle_inward->status_id == '8460' ? 'blue' : 'green';
				return '<span class="text-' . $status . '">' . $vehicle_inward->status . '</span>';
			})
			->addColumn('action', function ($vehicle_inward) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$output = '';
				$output .= '<a href="#!/inward-vehicle/view/' . $vehicle_inward->id . '" id = "" title="View"><img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				if ($vehicle_inward->status_id == 8460) {
					$output .= '<a href="#!/inward-vehicle/vehicle-detail/' . $vehicle_inward->id . '" id = "" title="Initiate" class="btn btn-secondary-dark btn-xs">Initiate</a>';
				}
				if ($vehicle_inward->status_id == 8461 && $vehicle_inward->is_customer_agreed == 1) {
					$output .= '<a href="#!/inward-vehicle/update-jc/form/' . $vehicle_inward->id . '" id = "" title="Update JC" class="btn btn-secondary-dark btn-xs">Update JC</a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getCustomerSearchList(Request $request) {
		return Customer::searchCustomer($request);
	}

	public function getVehicleModelSearchList(Request $request) {
		return VehicleModel::searchVehicleModel($request);
	}

	public function getCitySearchList(Request $r) {
		return City::searchCity($r);
	}

	public function getPartSearchList(Request $r) {
		return Part::searchPart($r);
	}

}