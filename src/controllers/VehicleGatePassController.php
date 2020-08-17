<?php

namespace Abs\GigoPkg;
use App\Config;
use App\GatePass;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class VehicleGatePassController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getVehicleGatePassFilter() {
		$params = [
			'config_type_id' => 48,
			'add_default' => true,
			'default_text' => "Select Status",
		];
		$this->data['extras'] = [
			'status_list' => Config::getDropDownList($params),
		];
		return response()->json($this->data);
	}

	public function getVehicleGatePassList(Request $request) {

		$vehicle_gate_passes = GatePass::select([
			'job_orders.driver_name',
			'job_orders.driver_mobile_number',
			'vehicles.registration_number',
			'models.model_name',
			'job_orders.number as job_card_number',
			'gate_passes.number as gate_pass_no',
			'configs.name as status',
			'gate_passes.id',
			'gate_logs.id as gate_log_id',
			'gate_passes.status_id',
			DB::raw('DATE_FORMAT(gate_passes.created_at,"%d/%m/%Y, %h:%s %p") as date_and_time'),
		])
			->join('job_orders', 'job_orders.id', 'gate_passes.job_order_id')
			->leftJoin('job_cards', 'job_cards.id', 'gate_passes.job_card_id')
			->join('gate_logs', 'gate_logs.job_order_id', 'job_orders.id')
			->join('vehicles', 'vehicles.id', 'job_orders.vehicle_id')
			->join('models', 'models.id', 'vehicles.model_id')
			->join('configs', 'configs.id', 'gate_passes.status_id')
			->where(function ($query) use ($request) {
				if (!empty($request->gate_pass_created_date)) {
					$query->whereDate('gate_passes.created_at', date('Y-m-d', strtotime($request->gate_pass_created_date)));
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->registration_number)) {
					$query->where('vehicles.registration_number', 'LIKE', '%' . $request->registration_number . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->driver_name)) {
					$query->where('job_orders.driver_name', 'LIKE', '%' . $request->driver_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->driver_mobile_number)) {
					$query->where('job_orders.driver_mobile_number', 'LIKE', '%' . $request->driver_mobile_number . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->model_id)) {
					$query->where('vehicles.model_id', $request->model_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->status_id)) {
					$query->where('gate_passes.status_id', $request->status_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->job_card_number)) {
					$query->where('job_orders.number', 'LIKE', '%' . $request->job_card_number . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->number)) {
					$query->where('gate_passes.number', 'LIKE', '%' . $request->number . '%');
				}
			})
		//->where('job_cards.outlet_id', Auth::user()->employee->outlet_id)
			->where('gate_passes.type_id', 8280) // Vehicle Gate Pass
			->orderBy('gate_passes.status_id', 'ASC')
			->orderBy('gate_passes.created_at', 'DESC')
			->groupBy('gate_passes.id');

		if (!Entrust::can('gate-out-all')) {
			if (Entrust::can('gate-out-mapped-outlet')) {
				$vehicle_gate_passes->whereIn('job_orders.outlet_id', Auth::user()->employee->outlets->pluck('id')->toArray());
			} elseif (Entrust::can('gate-out-own-outlet')) {
				$vehicle_gate_passes->where('job_orders.outlet_id', Auth::user()->employee->outlet_id);
			} else {
				$vehicle_gate_passes->where('gate_passes.created_by_id', Auth::user()->id);
			}

		}

		return Datatables::of($vehicle_gate_passes)
			->rawColumns(['status', 'action'])
			->editColumn('status', function ($vehicle_gate_pass) {
				$status = $vehicle_gate_pass->status_id == '8340' ? 'red' : 'green';
				return '<span class="text-' . $status . '">' . $vehicle_gate_pass->status . '</span>';
			})
			->addColumn('action', function ($vehicle_gate_pass) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$output = '';
				$output .= '<a href="#!/vehicle-gate-pass/view/' . $vehicle_gate_pass->gate_log_id . '" id = "" title="View"><img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				//Gate Out Pending
				// if ($vehicle_gate_pass->status_id == 8340) {
				// 	$output .= '<button class="btn btn-secondary-dark btn-sm" onclick="angular.element(this).scope().vehicleGateOut(' . $vehicle_gate_pass->gate_log_id . ' )" title="Gate Out">Confirm Gate Out</button>';
				// }
				return $output;
			})
			->make(true);
	}

}