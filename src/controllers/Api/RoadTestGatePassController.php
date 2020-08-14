<?php

namespace Abs\GigoPkg\Api;

use App\Http\Controllers\Controller;
use App\JobOrder;
use App\RoadTestGatePass;
use App\TradePlateNumber;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;

class RoadTestGatePassController extends Controller {
	public $successStatus = 200;

	public function __construct() {
		$this->success_code = 200;
		$this->permission_denied_code = 401;
	}

	public function getRoadTestGatePass(Request $request) {
		try {

			$road_test_gate_passes_list = RoadTestGatePass::select([
				'road_test_gate_pass.id',
				'job_orders.number as job_order_number',
				'road_test_gate_pass.number',
				'road_test_gate_pass.number as gate_pass_no',
				'road_test_gate_pass.status_id',
				'configs.name as status',
				'vehicles.registration_number',
				'models.model_name as model',
				'trade_plate_numbers.trade_plate_number',
				DB::raw('DATE_FORMAT(road_test_gate_pass.created_at,"%d/%m/%Y, %h:%s %p") as date_and_time'),
			])
				->join('job_orders', 'road_test_gate_pass.job_order_id', 'job_orders.id')
				->join('trade_plate_numbers', 'trade_plate_numbers.id', 'job_orders.road_test_trade_plate_number_id')
				->join('configs', 'configs.id', 'road_test_gate_pass.status_id')
				->join('vehicles', 'vehicles.id', 'job_orders.vehicle_id')
				->join('models', 'models.id', 'vehicles.model_id')
				->where(function ($query) use ($request) {
					if (!empty($request->search_key)) {
						$query->where('road_test_gate_pass.number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('job_orders.number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('configs.name', 'LIKE', '%' . $request->search_key . '%')
						;
					}
				})
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
					if (!empty($request->job_order_number)) {
						$query->where('job_orders.number', 'LIKE', '%' . $request->job_order_number . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->status_id)) {
						$query->where('road_test_gate_pass.status_id', $request->status_id);
					}
				})
				->orderBy('road_test_gate_pass.id', 'DESC')
			;

			if (!Entrust::can('view-all-outlet-material-gate-pass')) {
				if (Entrust::can('view-mapped-outlet-road-test-gate-pass')) {
					$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
					array_push($outlet_ids, Auth::user()->employee->outlet_id);
					$road_test_gate_passes_list->whereIn('job_orders.outlet_id', $outlet_ids);
				} else {
					$road_test_gate_passes_list->where('job_orders.outlet_id', Auth::user()->employee->outlet_id);
				}

			}
			$total_records = $road_test_gate_passes_list->get()->count();

			if ($request->offset) {
				$road_test_gate_passes_list->offset($request->offset);
			}
			if ($request->limit) {
				$road_test_gate_passes_list->limit($request->limit);
			}

			$road_test_gate_passes = $road_test_gate_passes_list->get();

			return response()->json([
				'success' => true,
				'road_test_gate_passes' => $road_test_gate_passes,
				'total_records' => $total_records,
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

	//VEHICLE INWARD VIEW DATA
	public function getRoadTestGatePassViewData(Request $r) {
		// dd($r->all());
		try {

			$road_test_gate_pass = RoadTestGatePass::with([
				'jobOrder',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
				'jobOrder.roadTestPreferedBy',
				'jobOrder.gateLog',
				'jobOrder.gateLog.vehicleAttachment',
				'jobOrder.gateLog.kmAttachment',
				'jobOrder.gateLog.driverAttachment',
				'jobOrder.gateLog.chassisAttachment',
				'status',
			])
				->find($r->gate_pass_id);

			if (!$road_test_gate_pass) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'error' => [
						'Road Test Gate Pass Not Found!',
					],
				]);
			}

			$road_test_gate_pass->gate_in_attachment_path = url('storage/app/public/gigo/gate_in/attachments/');

			return response()->json([
				'success' => true,
				'road_test_gate_pass' => $road_test_gate_pass,
				// 'customer_detail' => $customer,
			]);

		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

	public function saveRoadTestGateInAndOut(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'gate_pass_id' => [
					'required',
					'integer',
					'exists:road_test_gate_pass,id',
				],
				'type' => [
					'required',
					'string',
				],
				'gate_out_remarks' => [
					'nullable',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();
			$gate_pass = RoadTestGatePass::find($request->gate_pass_id);
			if ($gate_pass) {
				if ($request->type == 'Out') {
					$gate_pass->gate_out_date = Carbon::now();
					$gate_pass->gate_out_remarks = $request->gate_out_remarks ? $request->gate_out_remarks : NULL;
					$gate_pass->status_id = 8301;
				} else {
					$gate_pass->gate_in_date = Carbon::now();
					$gate_pass->status_id = 8302;

					$job_order = JobOrder::where('id', $gate_pass->job_order_id)->first();

					//TradePlateNUmber Status Update
					$trade_plate_number = TradePlateNumber::where('id', $job_order->road_test_trade_plate_number_id)->update(['status_id' => 8240, 'updated_at' => Carbon::now()]);
				}
				$gate_pass->updated_by_id = Auth::user()->id;
				$gate_pass->updated_at = Carbon::now();
				$gate_pass->save();
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'gate_pass' => $gate_pass,
				'type' => $request->type,
				'message' => 'Road Test Gate ' . $request->type . ' successfully completed !!',
			]);

		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}
}
