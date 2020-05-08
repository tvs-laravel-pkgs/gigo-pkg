<?php

namespace Abs\GigoPkg\Api;

use App\GateLog;
use App\Http\Controllers\Controller;
use App\Vehicle;
use App\VehicleModel;
use Auth;
use DB;
use Illuminate\Http\Request;
use Validator;

class VehicleInwardController extends Controller {
	public $successStatus = 200;

	public function getVehicleInwardList(Request $request)
	{
		try{
			$validator = Validator::make($request->all(), [
				'employee_id' => [
					'required',
					'exists:employees,id',
					'integer',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			$gate_log_ids=[];
			$gate_logs=GateLog::
			where('gate_logs.company_id',Auth::user()->company_id)
			->get();
			foreach ($gate_logs as $key => $gate_log) {
				if($gate_log->status_id==8120){ //Gate In Completed
					$gate_log_ids[]=$gate_log->id;
				}else{// Others
					if($gate_log->floor_adviser_id==$request->employee_id){
						$gate_log_ids[]=$gate_log->id;
					}
				}
			}
			$vehicle_inward_list=GateLog::select('gate_logs.*')
			->with([
				'vehicleDetail',
				'vehicleDetail.vehicleOwner',
				'vehicleDetail.vehicleOwner.CustomerDetail',
			])
			->leftJoin('vehicles','gate_logs.vehicle_id','vehicles.id')
			->leftJoin('vehicle_owners','vehicles.id','vehicle_owners.vehicle_id')
			->leftJoin('customers','vehicle_owners.customer_id','customers.id')
			->whereIn('gate_logs.id',$gate_log_ids)
			->where(function ($query) use ($request) {
				if (isset($request->search_key)) {
					$query->where('vehicles.registration_number', 'LIKE', '%' . $request->search_key . '%')
					->orWhere('customers.name', 'LIKE', '%' . $request->search_key . '%');
				}
			})
			->get();
		
			return response()->json([
				'success' => true,
				'vehicle_inward_list' => $vehicle_inward_list,
			]);
		}catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}
	public function getVehicleFomData(Request $request) {
		// dd($request->gate_log_id);
		try {
			$validator = Validator::make($request->all(), [
				'gate_log_id' => [
					'required',
					'exists:gate_logs,id',
					'integer',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			//UPDATE GATE LOG
			$gate_log = GateLog::where('id', $request->gate_log_id)
				->update([
					'status_id' => 8121, //VEHICLE INWARD INPROGRESS
					'floor_adviser_id' => Auth::user()->entity_id,
					'updated_by_id' => Auth::user()->id,
				]);

			$vehicle_detail = GateLog::with(['vehicleDetail'])->find($request->gate_log_id);

			$extras = [
				'registration_types' => [
					['id' => 0, 'name' => 'Unregistred'],
					['id' => 1, 'name' => 'Registred'],
				],
				'vehicle_models' => VehicleModel::getList(),
			];

			return response()->json([
				'success' => true,
				'vehicle_detail' => $vehicle_detail,
				'extras' => $extras,
			]);
			// return VehicleInward::saveVehicleGateInEntry($request);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveVehicle(Request $request) {
		// dd($request->all());

		try {
			//REMOVE WHITE SPACE BETWEEN REGISTRATION NUMBER
			$request->registration_number = str_replace(' ', '', $request->registration_number);

			//REGISTRATION NUMBER VALIDATION
			if ($request->registration_number) {
				$error = '';
				$first_two_string = substr($request->registration_number, 0, 2);
				$next_two_number = substr($request->registration_number, 2, 2);
				$last_two_number = substr($request->registration_number, -2);
				if (!preg_match('/^[A-Z]+$/', $first_two_string) && !preg_match('/^[0-9]+$/', $next_two_number) && !preg_match('/^[0-9]+$/', $last_two_number)) {
					$error = "Please enter valid registration number!";
				}
				if ($error) {
					return response()->json([
						'success' => false,
						'errors' => $error,
					]);
				}
			}

			$validator = Validator::make($request->all(), [
				'is_registered' => [
					'required',
					'integer',
				],
				'registration_number' => [
					'required',
					'min:6',
					'string',
					'max:10',
				],
				'model_id' => [
					'required',
					'exists:models,id',
					'integer',
				],
				'engine_number' => [
					'required',
					'min:7',
					'max:64',
					'string',
				],
				'chassis_number' => [
					'required',
					'min:10',
					'max:64',
					'string',
				],
				'vin_number' => [
					'required',
					'min:17',
					'max:32',
					'string',
				],
			]);

			if ($validator->fails()) {
				$errors = $validator->errors()->all();
				$success = false;
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();
			//VEHICLE GATE ENTRY DETAILS
			// UNREGISTRED VEHICLE DIFFERENT FLOW WAITING FOR REQUIREMENT
			if (!$request->is_registered == 1) {
				return response()->json([
					'success' => true,
					'message' => 'Unregistred Vehile Not allow!!',
				]);
			}

			//ONLY FOR REGISTRED VEHICLE
			$vehicle = Vehicle::firstOrNew([
				'company_id' => Auth::user()->company_id,
				'registration_number' => $request->registration_number,
			]);
			$vehicle->fill($request->all());
			$vehicle->status_id = 8141; //Customer Not Mapped
			$vehicle->company_id = Auth::user()->company_id;
			$vehicle->updated_by_id = Auth::user()->id;
			$vehicle->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Vehicle detail updated Successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	// public function getCustomerFomData(Request $request) {
	// 	dd($request->all());

	// 	$customer_detail = GateLog::with(['vehicleDetail'])->find($request->gate_log_id);

	// }

}
