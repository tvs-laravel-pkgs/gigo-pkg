<?php

namespace Abs\GigoPkg\Api;

use App\FloatingGatePass;
use App\Http\Controllers\Controller;
use App\JobOrder;
use App\TradePlateNumber;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;

class FloatingGatePassController extends Controller {
	public $successStatus = 200;

	public function __construct() {
		$this->success_code = 200;
		$this->permission_denied_code = 401;
	}

	public function getFloatingGatePass(Request $request) {
		try {

			$floating_gate_passes_list = FloatingGatePass::select([
				'floating_stock_logs.id',
				'job_cards.job_card_number as job_card_number',
				'floating_stock_logs.number as floating_gate_pass_no',
				'floating_stock_logs.status_id',
				'configs.name as status',
				'vehicles.registration_number',
				'models.model_name as model',
				'job_orders.driver_name',
				'job_orders.driver_mobile_number',
				DB::raw('DATE_FORMAT(floating_stock_logs.created_at,"%d/%m/%Y, %h:%s %p") as date_and_time'),
			])
				->join('job_cards', 'floating_stock_logs.job_card_id', 'job_cards.id')
				->join('job_orders', 'job_cards.job_order_id', 'job_orders.id')
				->join('vehicles', 'vehicles.id', 'job_orders.vehicle_id')
				->join('configs', 'configs.id', 'floating_stock_logs.status_id')
				->join('models', 'models.id', 'vehicles.model_id')
				->where(function ($query) use ($request) {
					if (!empty($request->search_key)) {
						$query->where('floating_stock_logs.number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('job_cards.job_card_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('configs.name', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('vehicles.registration_number', 'LIKE', '%' . $request->search_key . '%')
						;
					}
				})
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
				->orderBy('floating_stock_logs.created_at', 'DESC')
			;

			if (!Entrust::can('view-all-outlet-floating-gate-pass')) {
				if (Entrust::can('view-mapped-outlet-floating-gate-pass')) {
					$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
					array_push($outlet_ids, Auth::user()->employee->outlet_id);
					$floating_gate_passes_list->whereIn('floating_stock_logs.outlet_id', $outlet_ids);
				} else {
					$floating_gate_passes_list->where('floating_stock_logs.outlet_id', Auth::user()->employee->outlet_id);
				}

			}
			$total_records = $floating_gate_passes_list->get()->count();

			if ($request->offset) {
				$floating_gate_passes_list->offset($request->offset);
			}
			if ($request->limit) {
				$floating_gate_passes_list->limit($request->limit);
			}

			$floating_gate_passes = $floating_gate_passes_list->get();

			return response()->json([
				'success' => true,
				'floating_gate_passes' => $floating_gate_passes,
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
	public function getFloatingGatePassViewData(Request $r) {
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

	public function saveFloatingGateInAndOut(Request $request) {
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
