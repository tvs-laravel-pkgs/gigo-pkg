<?php

namespace Abs\GigoPkg;
use App\Config;
use App\FloatingGatePass;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class FloatingGatePassController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getFloatingGatePassFilter() {
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

	public function getFloatingGatePassList(Request $request) {

		$floating_gate_passes = FloatingGatePass::select([
			'floating_stock_logs.id',
			'job_cards.id as job_card_id',
			'floating_stock_logs.outward_date',
			'floating_stock_logs.inward_date',
			'job_cards.job_card_number as job_card_number',
			'floating_stock_logs.number as floating_gate_pass_no',
			'floating_stock_logs.status_id',
			// 'configs.name as status',
			'vehicles.registration_number',
			DB::raw('COUNT(floating_stock_logs.id) as no_of_parts'),
			DB::raw('DATE_FORMAT(floating_stock_logs.created_at,"%d/%m/%Y, %h:%s %p") as date_and_time'),
			DB::raw('CASE
                        WHEN count((CASE WHEN floating_stock_logs.status_id = "8300" THEN floating_stock_logs.status_id END )) > 0 THEN "Gate Out Pending"
                        WHEN count((CASE WHEN floating_stock_logs.status_id = "8303" THEN floating_stock_logs.status_id END )) > 0 THEN "GateIn Partial Completed"
                        WHEN count((CASE WHEN floating_stock_logs.status_id = "8302" THEN floating_stock_logs.status_id END )) =  COUNT(floating_stock_logs.id) THEN "GateIn Success"
                        WHEN count((CASE WHEN floating_stock_logs.status_id = "8301" THEN floating_stock_logs.status_id END )) > 0 THEN "GateIn Pending"
                        ELSE "Gate Out Pending" END AS status'),
		])
			->join('job_cards', 'floating_stock_logs.job_card_id', 'job_cards.id')
			->join('job_orders', 'job_cards.job_order_id', 'job_orders.id')
			->join('vehicles', 'vehicles.id', 'job_orders.vehicle_id')
		// ->join('configs', 'configs.id', 'floating_stock_logs.status_id')
			->where(function ($query) use ($request) {
				if (!empty($request->gate_pass_created_date)) {
					$query->whereDate('floating_stock_logs.created_at', date('Y-m-d', strtotime($request->gate_pass_created_date)));
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->number)) {
					$query->where('floating_stock_logs.number', 'LIKE', '%' . $request->number . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->job_card_number)) {
					$query->where('job_cards.job_card_number', 'LIKE', '%' . $request->job_card_number . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->status_id)) {
					$query->where('floating_stock_logs.status_id', $request->status_id);
				}
			})

			->groupBy('floating_stock_logs.job_card_id')
			->orderBy('floating_stock_logs.created_at', 'DESC');

		if (!Entrust::can('view-all-outlet-floating-gate-pass')) {
			if (Entrust::can('view-mapped-outlet-floating-gate-pass')) {
				$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
				array_push($outlet_ids, Auth::user()->employee->outlet_id);
				$floating_gate_passes->whereIn('floating_stock_logs.outlet_id', $outlet_ids);
			} else {
				$floating_gate_passes->where('floating_stock_logs.outlet_id', Auth::user()->employee->outlet_id);
			}
		}

		return Datatables::of($floating_gate_passes)
			->rawColumns(['status', 'action'])
			->editColumn('status', function ($floating_gate_passes) {
				$status = $floating_gate_passes->status_id == '8302' ? 'green' : $floating_gate_passes->status_id == '8301' ? 'blue' : 'red';
				return '<span class="text-' . $status . '">' . $floating_gate_passes->status . '</span>';
			})
			->addColumn('action', function ($floating_gate_passes) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$output = '';
				$output .= '<a href="#!/floating-gate-pass/view/' . $floating_gate_passes->job_card_id . '" id = "" title="View"><img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				return $output;
			})
			->make(true);
	}

}