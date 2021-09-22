<?php

namespace Abs\GigoPkg;
use App\Config;
use App\GatePass;
// use App\Otp;
use App\Http\Controllers\Controller;
use App\JobOrder;
use Auth;
use Carbon\Carbon;
use Entrust;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class OTPController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getOTPFilter() {
		$this->data['extras'] = [
			'event_list' => collect(Config::select('id', 'name')->where('config_type_id', 404)->get())->prepend(['id' => '', 'name' => 'Select Event Type']),
		];

		return response()->json($this->data);
	}

	public function getOTPList(Request $request) {
		// dd($request->all());
		$otps = Config::join('otps', 'otps.entity_type_id', 'configs.id')->select('otps.entity_type_id', 'otps.entity_id', 'otps.otp_no', 'otps.created_at', 'otps.expired_at', 'configs.name as type')

			->where('otps.expired_at', '>=', Carbon::now())

			->where(function ($query) use ($request) {
				if (!empty($request->event_id) && $request->event_id != '<%$ctrl.event_id%>') {
					$query->where('otps.entity_type_id', $request->event_id);
				}
			})

		;

		if (!Entrust::can('otp-all-outlet')) {
			if (Entrust::can('otp-mapped-outlet')) {
				$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
				array_push($outlet_ids, Auth::user()->employee->outlet_id);
				$otps->whereIn('otps.outlet_id', $outlet_ids);
			}else{
				$otps->where('otps.outlet_id', Auth::user()->employee->outlet_id);
			}
		}

		$otps->orderBy('otps.id', 'DESC')->get();

		return Datatables::of($otps)

			->addColumn('number', function ($otps) {
				if ($otps->entity_type_id == '10111') {
					$gate_pass = GatePass::where('id', $otps->entity_id)->first();
					return $gate_pass ? $gate_pass->number : '-';
				} else {
					$job_order = JobOrder::where('id', $otps->entity_id)->first();
					return $job_order ? $job_order->number : '-';
				}
			})

			->make(true);
	}
}