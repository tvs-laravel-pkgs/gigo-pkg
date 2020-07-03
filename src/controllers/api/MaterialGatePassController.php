<?php

namespace Abs\GigoPkg\Api;

use App\Customer;
use App\GatePass;
use App\Http\Controllers\Controller;
use App\JobCard;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;

class MaterialGatePassController extends Controller {
	public $successStatus = 200;

	public function __construct() {
		$this->success_code = 200;
		$this->permission_denied_code = 401;
	}

	public function getMaterialGatePass(Request $request) {
		try {
			$material_gate_passes_list = GatePass::select([
				'gate_passes.id',
				'job_cards.job_card_number',
				'gate_pass_details.work_order_no',
				'gate_pass_details.vendor_contact_no',
				'gate_passes.number as gate_pass_no',
				'gate_passes.status_id',
				'vendors.name',
				'vendors.code',
				'configs.name as status',
				DB::raw('DATE_FORMAT(gate_passes.created_at,"%d/%m/%Y, %h:%s %p") as date_and_time'),
				DB::raw('COUNT(gate_pass_items.id) as items'),
			])
				->join('job_cards', 'gate_passes.job_card_id', 'job_cards.id')
				->join('gate_pass_details', 'gate_pass_details.gate_pass_id', 'gate_passes.id')
				->join('configs', 'configs.id', 'gate_passes.status_id')
				->join('vendors', 'gate_pass_details.vendor_id', 'vendors.id')
				->join('gate_pass_items', 'gate_pass_items.gate_pass_id', 'gate_passes.id')
				->where(function ($query) use ($request) {
					if (!empty($request->search_key)) {
						$query->where('gate_passes.number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('gate_pass_details.work_order_no', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('job_cards.job_card_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('vendors.name', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('vendors.code', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('configs.name', 'LIKE', '%' . $request->search_key . '%')
						;
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->gate_pass_created_date)) {
						$query->whereDate('gate_passes.created_at', date('Y-m-d', strtotime($request->gate_pass_created_date)));
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->number)) {
						$query->where('gate_passes.number', 'LIKE', '%' . $request->number . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->job_card_number)) {
						$query->where('job_cards.job_card_number', 'LIKE', '%' . $request->job_card_number . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->work_order_no)) {
						$query->where('gate_pass_details.work_order_no', 'LIKE', '%' . $request->work_order_no . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->vendor_name)) {
						$query->where('vendors.name', 'LIKE', '%' . $request->vendor_name . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->vendor_code)) {
						$query->where('vendors.code', 'LIKE', '%' . $request->vendor_code . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->status_id)) {
						$query->where('gate_passes.status_id', $request->status_id);
					}
				})
				->where('job_cards.outlet_id', Auth::user()->employee->outlet_id)
				->where('gate_passes.type_id', 8281) // Material Gate Pass
				->orderBy('gate_passes.id', 'DESC')
				->groupBy('gate_passes.id')
			;
			$total_records = $material_gate_passes_list->get()->count();

			if ($request->offset) {
				$material_gate_passes_list->offset($request->offset);
			}
			if ($request->limit) {
				$material_gate_passes_list->limit($request->limit);
			}

			$material_gate_passes = $material_gate_passes_list->get();

			return response()->json([
				'success' => true,
				'material_gate_passes' => $material_gate_passes,
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
	public function getMaterialGatePassViewData(Request $r) {
		try {

			$material_gate_pass = GatePass::with([
				'jobCard',
				'status',
				'gatePassDetail',
				'gatePassDetail.vendor',
				'gatePassDetail.vendor.primaryAddress',
				'gatePassItems',
				'gatePassItems.attachment',
			])
				->where('type_id', 8281) //Material Gate pass
				->find($r->gate_pass_id);

			if (!$material_gate_pass) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'error' => [
						'Material Gate Pass Not Found!',
					],
				]);
			}
			if ($material_gate_pass->gatePassItems) {
				$material_gate_pass->items = count($material_gate_pass->gatePassItems);
			} else {
				$material_gate_pass->items = 0;
			}
			$material_gate_pass->attachement_path = url('storage/app/public/gigo/material_gate_pass/attachments/');

			//GET CUSTOMER INFO
			if ($material_gate_pass->jobCard->jobOrder->vehicle->currentOwner) {
				if ($material_gate_pass->jobCard->jobOrder->vehicle->currentOwner->customer) {
					$customer = $material_gate_pass->jobCard->jobOrder->vehicle->currentOwner->customer;
				} else {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Customer Not Found!',
						],
					]);
				}
			} else {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Customer Not Found!',
					],
				]);
			}

			return response()->json([
				'success' => true,
				'material_gate_pass' => $material_gate_pass,
				'customer_detail' => $customer,
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

	public function saveMaterialGateInAndOut(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'gate_pass_id' => [
					'required',
					'integer',
					'exists:gate_passes,id',
				],
				'remarks' => [
					'nullable',
					'string',
					'max:191',
				],
				'type' => [
					'required',
					'string',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$gate_pass = GatePass::find($request->gate_pass_id);
			if ($request->type == 'In') {
				DB::beginTransaction();
				GatePass::where('id', $request->gate_pass_id)->update([
					'status_id' => 8302, //Gate In Success
					'gate_in_date' => Carbon::now(),
					'gate_in_remarks' => $request->remarks ? $request->remarks : NULL,
					'updated_by_id' => Auth::user()->id,
					'updated_at' => Carbon::now(),
				]);
				DB::commit();
				return response()->json([
					'success' => true,
					'gate_pass' => $gate_pass,
					'type' => $request->type,
					'message' => 'Material Gate ' . $request->type . ' successfully completed !!',
				]);
			} else {
				$otp_response = $this->sendOtpToCustomer($gate_pass->id);
				return $otp_response;
			}

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

	public function sendOtpToCustomer($id) {
		try {
			$material_gate_pass = GatePass::where('id', $id)
				->where('type_id', 8281) //Material Gate pass
				->first();
			if (!$material_gate_pass) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Gate Pass Not Found!',
					],
				]);
			}

			$job_card = JobCard::find($material_gate_pass->job_card_id);

			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'JobCard Not Found!',
					],
				]);
			}

			$user = User::find($job_card->floor_supervisor_id);
			if (!$user) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Floor Supervisor Not Found!',
					],
				]);
			}

			$mobile_number = $user->contact_number;
			if (!$mobile_number) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Mobile Number Not Found!',
					],
				]);
			}

			DB::beginTransaction();
			$material_gate_pass_otp_update = GatePass::where('id', $id)->update([
				'otp_no' => mt_rand(111111, 999999),
				'updated_by_id' => Auth::user()->id,
				'updated_at' => Carbon::now(),
			]);

			DB::commit();
			if (!$material_gate_pass_otp_update) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Gate Pass OTP Update Failed',
					],
				]);
			}
			//Get material Gate pass After Otp Update
			$material_gate_pass = GatePass::find($id);
			$otp = $material_gate_pass->otp_no;

			$message = 'OTP is ' . $otp . ' for material gate out. Please enter OTP to verify your material gate out';

			$mobile_number = '9965098134';
			$msg = sendSMSNotification($mobile_number, $message);
			//dd($msg);
			//Enable After Sms Issue Resloved
			/*if(!$msg){
				return response()->json([
					'success' => false,
					'error' => 'OTP SMS Not Sent.Please Try again ',
				]);
			}*/
			return response()->json([
				'success' => true,
				'gate_pass' => $material_gate_pass,
				'user' => $user,
				'type' => 'Out',
				'message' => 'OTP Sent successfully!!',
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

	public function materialGateOutConfirm(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'gate_pass_id' => [
					'required',
					'integer',
					'exists:gate_passes,id',
				],
				'remarks' => [
					'nullable',
					'string',
					'max:191',
				],
				'otp_no' => [
					'required',
					'string',
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

			$gate_pass = GatePass::where('id', $request->gate_pass_id)
				->where('otp_no', '=', $request->otp_no)
				->first();
			if (!$gate_pass) {
				return response()->json([
					'success' => false,
					'error' => 'Gate pass OTP is worng. Please try again.',
				]);
			}

			//UPDATE GATE PASS
			GatePass::where('id', $request->gate_pass_id)->update([
				'status_id' => 8301, //Gate In Pending
				'gate_out_date' => Carbon::now(),
				'gate_out_remarks' => $request->remarks ? $request->remarks : NULL,
				'updated_by_id' => Auth::user()->id,
				'updated_at' => Carbon::now(),
			]);

			DB::commit();
			return response()->json([
				'success' => true,
				'gate_pass' => $gate_pass,
				'message' => 'Material Gate Out successfully completed!!',
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
