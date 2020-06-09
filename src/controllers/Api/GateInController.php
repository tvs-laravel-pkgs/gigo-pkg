<?php

namespace Abs\GigoPkg\Api;

use App\Config;
use App\GateLog;
use App\Http\Controllers\Controller;
use App\JobOrder;
use App\Vehicle;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Storage;
use Validator;

class GateInController extends Controller {
	public $successStatus = 200;

	public function getFormData() {
		try {
			$extras = [
				'reading_type_list' => Config::getDropDownList([
					'config_type_id' => 33,
					'default_text' => 'Select Reading type',
				]),
			];
			return response()->json([
				'success' => true,
				'extras' => $extras,
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

	public function createGateInEntry(Request $request) {
		try {
			//REMOVE WHITE SPACE BETWEEN REGISTRATION NUMBER
			$request->registration_number = str_replace(' ', '', $request->registration_number);

			//REGISTRATION NUMBER VALIDATION
			$error = '';
			if ($request->registration_number) {
				$registration_no_count = strlen($request->registration_number);
				if ($registration_no_count < 8) {
					return response()->json([
						'success' => false,
						'error' => 'The registration number must be at least 8 characters.',
					]);
				} else {
					$first_two_string = substr($request->registration_number, 0, 2);
					$next_two_number = substr($request->registration_number, 2, 2);
					$last_two_number = substr($request->registration_number, -2);
					if (!preg_match('/^[A-Z]+$/', $first_two_string) && !preg_match('/^[0-9]+$/', $next_two_number) && !preg_match('/^[0-9]+$/', $last_two_number)) {
						$error = "Please enter valid registration number!";
					}
					//issue : Vijay : wrong logic
					// if (!preg_match('/^[A-Z]+$/', $first_two_string) || !preg_match('/^[0-9]+$/', $next_two_number) || !preg_match('/^[0-9]+$/', $last_two_number)) {
					// 	$error = "Please enter valid registration number!";
					// }
					if ($error) {
						return response()->json([
							'success' => false,
							'error' => $error,
						]);
					}
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
					'required_if:is_registered,==,1',
					'max:10',
				],
				'vin_number' => [
					'required',
					'min:17',
					'max:32',
					'string',
				],
				'km_reading_type_id' => [
					'required',
					'integer',
					'exists:configs,id',
				],
				'km_reading' => [
					'required_if:km_reading_type_id,==,8040',
					'numeric',
				],
				'hr_reading' => [
					'required_if:km_reading_type_id,==,8041',
					'numeric',
				],
				'driver_name' => [
					'nullable',
					'min:3',
					'max:191',
					'string',
				],
				'driver_mobile_number' => [
					'nullable',
					'min:10',
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
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
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

			$job_order = new JobOrder;
			$job_order->company_id = Auth::user()->company_id;
			$job_order->number = rand();
			$job_order->fill($request->all());
			$job_order->vehicle_id = $vehicle->id;
			$job_order->outlet_id = Auth::user()->employee->outlet_id;
			$job_order->status_id = 8460; //Ready for Inward
			$job_order->save();
			$job_order->number = 'JO-' . $job_order->id;
			$job_order->save();

			//NEW GATE IN ENTRY
			$gate_log = new GateLog;
			$gate_log->fill($request->all());
			$gate_log->company_id = Auth::user()->company_id;
			$gate_log->job_order_id = $job_order->id;
			$gate_log->created_by_id = Auth::user()->id;
			$gate_log->gate_in_date = Carbon::now();
			$gate_log->status_id = 8120; //GATE IN COMPLETED
			$gate_log->outlet_id = Auth::user()->employee->outlet_id;
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
			$gate_in_data['number'] = $gate_log->number;
			$gate_in_data['registration_number'] = $vehicle->registration_number;

			return response()->json([
				'success' => true,
				'gate_log' => $gate_in_data,
				'message' => 'Gate Entry Saved Successfully!!',
			]);

		} catch (Exception $e) {
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
