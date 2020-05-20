<?php

namespace Abs\GigoPkg\Api;

use App\GateLog;
use App\GatePass;
use App\Http\Controllers\Controller;
use App\Vehicle;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Storage;
use Validator;

class VehicleGatePassController extends Controller {
	public $successStatus = 200;

	public function saveVehicleGateInEntry(Request $request) {
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
				'vehicle_photo' => [
					'required',
					'mimes:jpeg,jpg,png',
					// 'max:3072',
				],
				'km_reading_photo' => [
					'required',
					'mimes:jpeg,jpg,png',
					// 'max:3072',
				],
				'driver_photo' => [
					'required',
					'mimes:jpeg,jpg,png',
					// 'max:3072',
				],
				'is_registered' => [
					'required',
					'integer',
				],
				'registration_number' => [
					'required' => Rule::requiredIf($request->is_registered == 1),
					// 'min:6',
					// 'string',
					'max:10',
				],
				'km_reading' => [
					'required',
					'numeric',
				],
				'driver_name' => [
					'nullable',
					'max:191',
					'string',
				],
				'contact_number' => [
					'nullable',
					'max:10',
					'string',
				],
				'gate_in_remarks' => [
					'nullable',
					'max:191',
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
			$vehicle->status_id = 8140; //NEW
			$vehicle->company_id = Auth::user()->company_id;
			$vehicle->created_by_id = Auth::user()->id;
			$vehicle->save();

			//NEW GATE IN ENTRY
			$gate_log = new GateLog;
			$gate_log->fill($request->all());
			$gate_log->company_id = Auth::user()->company_id;
			$gate_log->created_by_id = Auth::user()->id;
			$gate_log->gate_in_date = Carbon::now();
			$gate_log->status_id = 8120; //GATE IN COMPLETED
			$gate_log->vehicle_id = $vehicle->id;
			$gate_log->save();

			$gate_log->number = 'GI' . $gate_log->id;
			$gate_log->save();

			//CREATE DIRECTORY TO STORAGE PATH
			$attachment_path = storage_path('app/public/gigo/gate_in/attachments/');
			Storage::makeDirectory($attachment_path, 0777);

			//SAVE VEHICLE PHOTO ATTACHMENT
			if (!empty($request->vehicle_photo)) {
				$attachment = $request->vehicle_photo;
				$entity_id = $gate_log->id;
				$attachment_of_id = 225; //GATE LOG
				$attachment_type_id = 247; //VEHICLE PHOTO
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}

			//SAVE KM READING PHOTO
			if (!empty($request->km_reading_photo)) {
				$attachment = $request->km_reading_photo;
				$entity_id = $gate_log->id;
				$attachment_of_id = 225; //GATE LOG
				$attachment_type_id = 248; //KM READING PHOTO
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}

			//SAVE DRIVER PHOTO
			if (!empty($request->driver_photo)) {
				$attachment = $request->driver_photo;
				$entity_id = $gate_log->id;
				$attachment_of_id = 225; //GATE LOG
				$attachment_type_id = 249; //DRIVER PHOTO
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}

			DB::commit();
			$gate_in_data['gate_in_number'] = $gate_log->number;
			$gate_in_data['vehicle_number'] = $vehicle->registration_number;

			return response()->json([
				'success' => true,
				'gate_in_data' => $gate_in_data,
				'message' => 'Gate Entry Saved Successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}

	}

	public function getVehicleGatePassList(Request $request) {
		// dd($request->all());
		try {
			$vehicle_gate_pass_list = GateLog::select([
				'gate_logs.id as gate_log_id',
				'gate_logs.driver_name',
				'gate_logs.contact_number',
				'vehicles.registration_number',
				'models.model_name',
				'job_cards.job_card_number',
				'gate_passes.number as gate_pass_no',
				'configs.name as status',
				'gate_logs.status_id',
				DB::raw('DATE_FORMAT(gate_logs.gate_in_date,"%d/%m/%Y %h:%s %p") as gate_in_date_time'),
			])
				->join('vehicles', 'vehicles.id', 'gate_logs.vehicle_id')
				->join('models', 'models.id', 'vehicles.model_id')
				->join('gate_passes', 'gate_passes.id', 'gate_logs.gate_pass_id')
				->join('job_orders', 'job_orders.gate_log_id', 'gate_logs.id')
				->join('job_cards', 'job_cards.job_order_id', 'job_orders.id')
				->join('configs', 'configs.id', 'gate_logs.status_id')
				->where(function ($query) use ($request) {
					if (!empty($request->search_key)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('gate_logs.driver_name', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('gate_logs.contact_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('models.model_name', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('job_cards.job_card_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('gate_passes.number', 'LIKE', '%' . $request->search_key . '%')
						;
					}
				})
				->whereIn('gate_logs.status_id', [8123, 8124]) //GATE OUT PENDING, GATE OUT COMPLETED
				->get()
			;

			$available_gate_passes = count($vehicle_gate_pass_list);

			return response()->json([
				'success' => true,
				'data' => $vehicle_gate_pass_list, //NAME CHANGED FOR WEB DATATABLE LIST
				// 'vehicle_gate_pass_list' => $vehicle_gate_pass_list,
				'available_gate_passes' => $available_gate_passes,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function viewVehicleGatePass($gate_log_id) {
		// dd($gate_log_id);
		try {
			$view_vehicle_gate_pass = GateLog::
				with([
				'vehicleAttachment',
				'kmAttachment',
				'driverAttachment',
				'status',
				'gatePass',
				'vehicleDetail',
				'vehicleDetail.vehicleModel',
				'jobOrder',
				'jobOrder.jobCard',
				'jobOrder.jobCard.jobCardReturnableItems',
				'jobOrder.jobCard.jobCardReturnableItems.attachment',
			])
				->find($gate_log_id)
			;

			$view_vehicle_gate_pass->gate_in_attachement_path = url('storage/app/public/gigo/gate_in/attachments/');
			$view_vehicle_gate_pass->returnable_item_attachement_path = url('storage/app/public/gigo/job_card/returnable_items/');

			if (!$view_vehicle_gate_pass) {
				return response()->json([
					'success' => false,
					'error' => 'Vehicle Gate Pass Not Found!',
				]);
			}

			// CHANGE FORMAT OF GATE IN DATE AND TIME
			if (!empty($view_vehicle_gate_pass)) {
				$view_vehicle_gate_pass->gate_in_date_time = date('d/m/Y h:i a', strtotime($view_vehicle_gate_pass->gate_in_date));
			}

			return response()->json([
				'success' => true,
				'view_vehicle_gate_pass' => $view_vehicle_gate_pass,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveVehicleGateOutEntry(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'gate_log_id' => [
					'required',
					'integer',
					'exists:gate_logs,id',
				],
				'remarks' => [
					'nullable',
					'string',
					'max:191',
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

			$gate_log = GateLog::with(['vehicleDetail'])->find($request->gate_log_id);

			$gate_log_update = GateLog::where('id', $request->gate_log_id)
				->update([
					'gate_out_date' => Carbon::now(),
					'gate_out_remarks' => $request->remarks ? $request->remarks : NULL,
					'status_id' => 8124, //GATE OUT COMPLETED
					'updated_by_id' => Auth::user()->id,
					'updated_at' => Carbon::now(),
				]);

			if ($gate_log_update) {
				$gate_pass = GatePass::find($gate_log->gate_pass_id);
				if (!$gate_pass) {
					return response()->json([
						'success' => false,
						'error' => 'Gate Pass Not Found!',
					]);
				}

				$gate_pass_update = GatePass::where('id', $gate_pass->id)
					->update([
						'gate_out_date' => Carbon::now(),
						'updated_by_id' => Auth::user()->id,
						'updated_at' => Carbon::now(),
					]);
			}

			DB::commit();

			$gate_out_data['gate_pass_no'] = !empty($gate_pass->number) ? $gate_pass->number : NULL;
			$gate_out_data['registration_number'] = !empty($gate_log->vehicleDetail) ? $gate_log->vehicleDetail->registration_number : NULL;

			return response()->json([
				'success' => true,
				'gate_out_data' => $gate_out_data,
				'message' => 'Vehicle Gate Out successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

}
