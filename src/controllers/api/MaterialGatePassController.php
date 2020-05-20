<?php

namespace Abs\GigoPkg\Api;

use App\Customer;
use App\GatePass;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Validator;
use Carbon\Carbon;
use Auth;

class MaterialGatePassController extends Controller {
	public $successStatus = 200;

	public function __construct() {
		$this->success_code = 200;
		$this->permission_denied_code = 401;
	}

	public function getMaterialGatePass(Request $request){
		try {
			
			$material_gate_pass_details = GatePass::select([
				'gate_passes.id as gate_pass_id',
				'job_cards.job_card_number',
				'gate_pass_details.work_order_no',
				'gate_pass_details.vendor_contact_no',
				'gate_passes.number as gate_pass_no',
				'vendors.name',
				'vendors.code',
				'configs.name as status',
				DB::raw('DATE_FORMAT(gate_passes.gate_in_date,"%d/%m/%Y %h:%s %p") as gate_in_date_time'),
				DB::raw('COUNT(gate_pass_items.id) as items')
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
						;
					}
				})
				->groupBy('gate_passes.id')
				->where('gate_passes.type_id', 8281) // Material Gate Pass
				->get()
			;
			return response()->json([
				'success' => true,
				'data' => $material_gate_pass_details, //Name Changed For Web List
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

		//VEHICLE INWARD VIEW DATA
	public function getMaterialGatePassViewData($id) {
		try {
			//dd($id);
			$material_gate_pass = GatePass::where('id',$id)
			->where('type_id',8281)//Material Gate pass
			->first();
			if (!$material_gate_pass) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Pass Not Found!',
				]);
			}

			$material_gate_pass_detail = GatePass::with([
				'jobCard',
				'status',
				'gatePassDetail',
				'gatePassDetail.vendor',
				'gatePassItems',
				'gatePassItems.attachments'
			])
				->find($id);
			$material_gate_pass_detail->attachement_path = url('storage/app/public/gigo/gate_pass/attachments/');
			//material attachment path need to change 
			return response()->json([
				'success' => true,
				'material_gate_pass_detail' => $material_gate_pass_detail,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

public function materialGateInAndOut(Request $request) {
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
				$errors = $validator->errors()->all();
				$success = false;
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();

			$gate_pass = GatePass::find($request->gate_pass_id);
				if($request->type=='In'){
					$gate_pass_update = GatePass::where('id', $gate_pass->id)
						->update([
							'gate_in_date' => Carbon::now(),
							'gate_in_remarks' => $request->remarks ? $request->remarks : NULL,
							'updated_by_id' => Auth::user()->id,
							'updated_at' => Carbon::now(),
						]);
				}else{

						$this->materialCustomerOtp($gate_pass->id);
				}
				
			DB::commit();

			$gate_pass_data['gate_pass_no'] = !empty($gate_pass->number) ? $gate_pass->number : NULL;

			return response()->json([
				'success' => true,
				'gate_pass_data' => $gate_pass_data,
				'message' => 'Material Gate '.$request->type.' successfully completed !!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
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
				$errors = $validator->errors()->all();
				$success = false;
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();

			$gate_pass = GatePass::find($request->gate_pass_id);
			$otp_validate=GatePass::where('id',$request->gate_pass_id)
			->where('otp_no','=',$request->otp_no)
			->first();
			if(!$otp_validate){
				return response()->json([
					'success' => false,
					'error' => 'Gate pass OTP is worng. Please try again.',
				]);
			}
				
			$gate_pass_update = GatePass::where('id', $gate_pass->id)
				->update([
					'gate_out_date' => Carbon::now(),
					'gate_out_remarks' => $request->remarks ? $request->remarks : NULL,
					'updated_by_id' => Auth::user()->id,
					'updated_at' => Carbon::now(),
				]);
			if(!$gate_pass_update){
				return response()->json([
					'success' => false,
					'error' => 'Gate Pass Update Failed.',
				]);
			}

			DB::commit();

			$gate_out_data['gate_pass_no'] = !empty($gate_pass->number) ? $gate_pass->number : NULL;

			return response()->json([
				'success' => true,
				'gate_out_data' => $gate_out_data,
				'message' => 'Material Gate Out successfully completed!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function materialCustomerOtp($id){
		$material_gate_pass = GatePass::where('id',$id)
			->where('type_id',8281) //Material Gate pass
			->first();
			if (!$material_gate_pass) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Pass Not Found!',
				]);
			}
			$customer_details=Customer::select('name','mobile_no')
			->join('vehicle_owners','vehicle_owners.customer_id','customers.id')
			->join('vehicles','vehicle_owners.vehicle_id','vehicles.id')
			->join('gate_logs','gate_logs.vehicle_id','vehicles.id')
			->join('job_orders','job_orders.gate_log_id','gate_logs.id')
			->join('job_cards','job_cards.job_order_id','job_orders.id')
			->join('gate_passes','gate_passes.job_card_id','job_cards.id')
			->where('gate_passes.id',$material_gate_pass->id)
			->orderBy('vehicle_owners.from_date','DESC')
			 ->first();
			//dd($customer_details);
			if(!$customer_details){
				return response()->json([
					'success' => false,
					'error' => 'Customer Details Not Found!',
				]);
			}
			DB::beginTransaction();
			$material_gate_pass_otp_update = GatePass::where('id', $material_gate_pass->id)
						->update([
							'otp_no' => mt_rand(11111,99999),
							'updated_by_id' => Auth::user()->id,
							'updated_at' => Carbon::now(),
						]);

			DB::commit();
			if(!$material_gate_pass_otp_update){
				return response()->json([
					'success' => false,
					'error' => 'Gate Pass OTP Update Failed',
				]);
			}
			$otp=$material_gate_pass->otp_no;
			$mobile_number=$customer_details->mobile_no;
			$mobile_number='8838118082'; //saravanan mobile for testing
			//dd($mobile_number);
			$message='OTP is '.$otp.' for material gate out. Please enter OTP to verify your material gate out';
			if(!$mobile_number){
			return response()->json([
					'success' => false,
					'error' => 'Customer Mobile Number Not Found',
				]);			
			}
			$msg=sendSMSNotification($mobile_number,$message);
			//dd($msg);
			if(!$msg){
				return response()->json([
					'success' => false,
					'error' => 'OTP SMS not sent.Please Try again ',
				]);	
			}
			return response()->json([
				'success' => true,
				'material_gate_pass' => $material_gate_pass,
				'message' => 'OTP Sent successfully!!',
			]);
	}
}
