<?php

namespace Abs\GigoPkg\Api;

use App\Customer;
use App\Entity;
use App\GatePass;
use App\GatePassDetail;
use App\GatePassItem;
use App\Http\Controllers\Controller;
use App\JobCard;
use App\MaterialInwardLog;
use App\Otp;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
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
			if (!Entrust::can('view-all-outlet-material-gate-pass')) {
				if (Entrust::can('view-mapped-outlet-material-gate-pass')) {
					$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
					array_push($outlet_ids, Auth::user()->employee->outlet_id);
					$material_gate_passes_list->whereIn('job_cards.outlet_id', $outlet_ids);
				} else if (Entrust::can('view-own-outlet-material-gate-pass')) {
					$material_gate_passes_list->where('job_cards.outlet_id', Auth::user()->employee->outlet_id);
				} else {
					$material_gate_passes_list->where('gate_passes.created_by_id', Auth::user()->id);
				}

			}
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

			$gate_pass = GatePass::with(['gatePassItems'])->find($request->gate_pass_id);
			if ($request->type == 'In') {

				DB::beginTransaction();
				$total_qty = 0;
				$total_return_qty = 0;
				foreach ($request->gate_pass_items as $gate_pass_item) {
					$gate_pass_item_detail = GatePassItem::find($gate_pass_item['id']);
					$return_qty = $gate_pass_item_detail->return_qty + $gate_pass_item['return_qty'];
					$gate_pass_item_detail->return_qty = $return_qty;
					if ($return_qty > 0 && $return_qty != $gate_pass_item_detail->qty) {
						$gate_pass_item_detail->status_id = 11122; //PARTIAL
					} elseif ($gate_pass_item_detail->qty == $return_qty) {
						$gate_pass_item_detail->status_id = 11123; //COMPLETED
					} else {
						$gate_pass_item_detail->status_id = 11121; //PENDING
					}

					$gate_pass_item_detail->save();

					if ($gate_pass_item['return_qty'] > 0) {
						$material_inard_log = new MaterialInwardLog;
						$material_inard_log->gass_pass_item_id = $gate_pass_item_detail->id;
						$material_inard_log->qty = $gate_pass_item['return_qty'];
						$material_inard_log->created_by_id = Auth::user()->id;
						$material_inard_log->created_at = Carbon::now();
						$material_inard_log->save();
					}

					$total_qty += $gate_pass_item_detail->qty;
					$total_return_qty += $gate_pass_item_detail->return_qty;
				}

				if ($total_qty != $total_return_qty) {
					$status = 8303; //Gate In Partial Completed
				} else {
					$status = 8302; //Gate In Success
				}

				GatePass::where('id', $request->gate_pass_id)->update([
					'status_id' => $status,
					'gate_in_date' => Carbon::now(),
					'gate_in_remarks' => $request->remarks ? $request->remarks : NULL,
					'updated_by_id' => Auth::user()->id,
					'updated_at' => Carbon::now(),
				]);

				DB::commit();
				if ($status == 8303) {
					return response()->json([
						'success' => true,
						'gate_pass' => $gate_pass,
						'type' => $request->type,
						'message' => 'Material Gate ' . $request->type . ' partially completed !!',
					]);
				} else {
					return response()->json([
						'success' => true,
						'gate_pass' => $gate_pass,
						'type' => $request->type,
						'message' => 'Material Gate ' . $request->type . ' successfully completed !!',
					]);
				}
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

			DB::beginTransaction();

			$otp_no = mt_rand(111111, 999999);

			$material_gate_pass->otp_no = $otp_no;
			$material_gate_pass->updated_by_id = Auth::user()->id;
			$material_gate_pass->updated_at = Carbon::now();
			$material_gate_pass->save();

			$current_time = date("Y-m-d H:m:s");

			$expired_time = Entity::where('entity_type_id', 32)->select('name')->first();
			if ($expired_time) {
				$expired_time = date("Y-m-d H:i:s", strtotime('+' . $expired_time->name . ' hours', strtotime($current_time)));
			} else {
				$expired_time = date("Y-m-d H:i:s", strtotime('+1 hours', strtotime($current_time)));
			}

			//Otp Save
			$otp = new Otp;
			$otp->entity_type_id = 10111;
			$otp->entity_id = $material_gate_pass->id;
			$otp->otp_no = $otp_no;
			$otp->created_by_id = Auth::user()->id;
			$otp->created_at = $current_time;
			$otp->expired_at = $expired_time;
			$otp->save();

			DB::commit();
			if (!$material_gate_pass) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Gate Pass OTP Update Failed',
					],
				]);
			}
			//Get material Gate pass After Otp Update
			$otp = $material_gate_pass->otp_no;

			$gate_pass_detail = GatePassDetail::where('gate_pass_id', $material_gate_pass->id)->first();
			if (!$gate_pass_detail) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Vendor Details not found!',
					],
				]);
			}

			$message = 'OTP is ' . $otp . ' for Material Gate Pass. Please show this SMS to Our Security to verify your Material gate Pass';

			if ($gate_pass_detail->vendor_contact_no) {
				$msg = sendSMSNotification($gate_pass_detail->vendor_contact_no, $message);
			}

			return response()->json([
				'success' => true,
				'gate_pass' => $material_gate_pass,
				'mobile_number' => $gate_pass_detail->vendor_contact_no,
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

			$current_time = date("Y-m-d H:m:s");

			//Validate OTP -> Expired or Not
			$otp_validate = OTP::where('entity_type_id', 10111)->where('entity_id', $request->gate_pass_id)->where('otp_no', '=', $request->otp_no)->where('expired_at', '>=', $current_time)
				->first();
			if (!$otp_validate) {
				return response()->json([
					'success' => false,
					'error' => 'OTP Expired',
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
