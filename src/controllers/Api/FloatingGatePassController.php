<?php

namespace Abs\GigoPkg\Api;

use App\FloatingGatePass;
use App\Http\Controllers\Controller;
use App\JobCard;
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
				// 'configs.name as status',
				'job_cards.id as job_card_id',
				'vehicles.registration_number',
				'models.model_name as model',
				'job_orders.driver_name',
				'job_orders.driver_mobile_number',
				DB::raw('DATE_FORMAT(floating_stock_logs.created_at,"%d/%m/%Y, %h:%s %p") as date_and_time'),
				DB::raw('CASE
                        WHEN count((CASE WHEN floating_stock_logs.status_id = "11161" THEN floating_stock_logs.status_id END )) > 0 THEN "Gate Out Pending"
                        WHEN count((CASE WHEN floating_stock_logs.status_id = "11164" THEN floating_stock_logs.status_id END )) > 0 THEN "GateIn Partial Completed"
                        WHEN count((CASE WHEN floating_stock_logs.status_id = "11163" THEN floating_stock_logs.status_id END )) =  count(floating_stock_logs.id) THEN "GateIn Success"
                        WHEN count((CASE WHEN floating_stock_logs.status_id = "11162" THEN floating_stock_logs.status_id END )) > 0 THEN "GateIn Pending"
                        ELSE "Gate Out Pending" END AS status'),
			])
				->join('job_cards', 'floating_stock_logs.job_card_id', 'job_cards.id')
				->join('job_orders', 'job_cards.job_order_id', 'job_orders.id')
				->join('vehicles', 'vehicles.id', 'job_orders.vehicle_id')
			// ->join('configs', 'configs.id', 'floating_stock_logs.status_id')
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
				->groupBy('floating_stock_logs.job_card_id')
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
			$floating_gate_pass = JobCard::with([
				'jobOrder',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
				'jobOrder.gateLog',
				'jobOrder.gateLog.vehicleAttachment',
				'jobOrder.gateLog.kmAttachment',
				'jobOrder.gateLog.driverAttachment',
				'jobOrder.gateLog.chassisAttachment',
				'floatLogs',
				'floatLogs.floatStock',
				'floatLogs.floatStock.part',
				'floatLogs.status',
			])
				->find($r->id);

			if (!$floating_gate_pass) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['Job Card Not Found!'],
				]);
			}

			// $floating_gate_pass = FloatingGatePass::with([
			// 	'jobCard',
			// 	'jobCard.jobOrder',
			// 	'jobCard.jobOrder.vehicle',
			// 	'jobCard.jobOrder.vehicle.model',
			// 	'jobCard.jobOrder.roadTestPreferedBy',
			// 	'jobCard.jobOrder.gateLog',
			// 	'jobCard.jobOrder.gateLog.vehicleAttachment',
			// 	'jobCard.jobOrder.gateLog.kmAttachment',
			// 	'jobCard.jobOrder.gateLog.driverAttachment',
			// 	'jobCard.jobOrder.gateLog.chassisAttachment',
			// 	'status',
			// ])
			// 	->find($r->gate_pass_id);

			// if (!$floating_gate_pass) {
			// 	return response()->json([
			// 		'success' => false,
			// 		'error' => 'Validation Error',
			// 		'error' => [
			// 			'Floating Gate Pass Not Found!',
			// 		],
			// 	]);
			// }
			$floating_gate_pass->floating_gate_out_length = FloatingGatePass::where('status_id', 11161)->count();

			$floating_gate_pass->gate_in_attachment_path = url('storage/app/public/gigo/gate_in/attachments/');

			return response()->json([
				'success' => true,
				'floating_gate_pass' => $floating_gate_pass,
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
				'job_card_id' => [
					'required',
					'integer',
					'exists:job_cards,id',
				],
				'type' => [
					'required',
					'string',
				],
				'remarks' => [
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
			if ($request->type == 'Out') {
				//Update Floating gatepass status
				FloatingGatePass::where('job_card_id', $request->job_card_id)->where('status_id', 11161)->update(['status_id' => 11162, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now(), 'outward_date' => Carbon::now(), 'outward_remarks' => $request->remarks ? $request->remarks : NULL]);
			}
			// $gate_pass = FloatingGatePass::find($request->gate_pass_id);
			// if ($gate_pass) {
			// 	if ($request->type == 'Out') {
			// 		$gate_pass->outward_date = Carbon::now();
			// 		$gate_pass->outward_remarks = $request->gate_out_remarks ? $request->gate_out_remarks : NULL;
			// 		$gate_pass->status_id = 8301;
			// 	} else {
			// 		$gate_pass->inward_date = Carbon::now();
			// 		$gate_pass->status_id = 8302;
			// 	}
			// 	$gate_pass->updated_by_id = Auth::user()->id;
			// 	$gate_pass->updated_at = Carbon::now();
			// 	$gate_pass->save();
			// }

			DB::commit();

			return response()->json([
				'success' => true,
				'type' => $request->type,
				'message' => 'Floating Gate ' . $request->type . ' successfully completed !!',
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
