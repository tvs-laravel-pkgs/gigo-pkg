<?php

namespace Abs\GigoPkg;
use App\Config;
use App\Http\Controllers\Controller;
use App\RoadTestGatePass;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class RoadTestGatePassController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getRoadTestGatePassFilter() {
		$params = [
			'config_type_id' => 46,
			'add_default' => true,
			'default_text' => "Select Status",
		];
		$this->data['extras'] = [
			'status_list' => Config::getDropDownList($params),
		];
		return response()->json($this->data);
	}

	public function getRoadTestGatePassList(Request $request) {

		$road_test_gate_passes = RoadTestGatePass::select([
			'road_test_gate_pass.id',
			'job_orders.number as job_order_number',
			'road_test_gate_pass.number',
			'road_test_gate_pass.number as gate_pass_no',
			'road_test_gate_pass.status_id',
			'configs.name as status',
			'vehicles.registration_number',
			DB::raw('DATE_FORMAT(road_test_gate_pass.created_at,"%d/%m/%Y, %h:%s %p") as date_and_time'),
		])
			->join('job_orders', 'road_test_gate_pass.job_order_id', 'job_orders.id')
			->join('configs', 'configs.id', 'road_test_gate_pass.status_id')
			->join('vehicles', 'vehicles.id', 'job_orders.vehicle_id')
			->where(function ($query) use ($request) {
				if (!empty($request->gate_pass_created_date)) {
					$query->whereDate('road_test_gate_pass.created_at', date('Y-m-d', strtotime($request->gate_pass_created_date)));
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->number)) {
					$query->where('road_test_gate_pass.number', 'LIKE', '%' . $request->number . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->job_card_number)) {
					$query->where('job_orders.number', 'LIKE', '%' . $request->job_order_number . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->status_id)) {
					$query->where('road_test_gate_pass.status_id', $request->status_id);
				}
			})

			->orderBy('road_test_gate_pass.created_at', 'DESC');

		if (!Entrust::can('view-all-outlet-road-test-gate-pass')) {
			if (Entrust::can('view-mapped-outlet-road-test-gate-pass')) {
				$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
				array_push($outlet_ids, Auth::user()->employee->outlet_id);
				$road_test_gate_passes->whereIn('job_orders.outlet_id', $outlet_ids);
			} else {
				$road_test_gate_passes->where('job_orders.outlet_id', Auth::user()->employee->outlet_id);
			}
		}

		return Datatables::of($road_test_gate_passes)
			->rawColumns(['status', 'action'])
			->editColumn('status', function ($road_test_gate_passes) {
				$status = $road_test_gate_passes->status_id == '8302' ? 'green' : $road_test_gate_passes->status_id == '8301' ? 'blue' : 'red';
				return '<span class="text-' . $status . '">' . $road_test_gate_passes->status . '</span>';
			})
			->addColumn('action', function ($road_test_gate_passes) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$output = '';
				$output .= '<a href="#!/road-test-gate-pass/view/' . $road_test_gate_passes->id . '" id = "" title="View"><img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				return $output;
			})
			->make(true);
	}

}