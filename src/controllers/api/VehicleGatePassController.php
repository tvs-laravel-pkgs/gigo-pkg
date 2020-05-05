<?php

namespace Abs\GigoPkg\Api;

use App\Attachment;
use App\GateLog;
use App\Http\Controllers\Controller;
use App\Vehicle;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Storage;
use Validator;

class VehicleGatePassController extends Controller {
	public function __construct() {
		$this->success_code = 200;
		$this->permission_denied_code = 401;
	}

	public function saveVehicleGateInEntry(Request $request) {
		// dd($request->all());

		try {
			//REGISTRATION NUMBER VALIDATION EX: TN28 AA8888
			if (!preg_match('/[a-z]{2}( |)\d{2}(?: |,)(?:[a-z\d]{1}[a-z])\1\d{4}/i', $request->registration_number)) {
				return response()->json([
					'success' => false,
					'errors' => ['Registration Number Not Valid!'],
				], $this->success_code);
			}

			$validator = Validator::make($request->all(), [
				'vehicle_photo' => [
					'required:true',
					'mimes:jpeg,jpg,png,bmp,tif,tiff,gif,eps',
					// 'max:3072',
				],
				'km_reading_photo' => [
					'required:true',
					'mimes:jpeg,jpg,png,bmp,tif,tiff,gif,eps',
					// 'max:3072',
				],
				'driver_photo' => [
					'required:true',
					'mimes:jpeg,jpg,png,bmp,tif,tiff,gif,eps',
					// 'max:3072',
				],
				'is_registered' => [
					'required:true',
					'integer',
				],
				'registration_number' => [
					'required:true',
					'string',
					'max:11',
					'unique:vehicles,registration_number,' . $request->id . ',id,company_id,' . $request->user()->company_id,
				],
				'km_reading' => [
					'required:true',
					'numeric',
				],
			]);

			if ($validator->fails()) {
				$errors = $validator->errors()->all();
				$success = false;
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				], $this->success_code);
			}

			DB::beginTransaction();
			//NEW GATE IN ENTRY
			$gate_log = new GateLog;
			$gate_log->fill($request->all());
			$gate_log->company_id = $request->user()->company_id;
			$gate_log->created_by_id = $request->user()->id;
			$gate_log->created_at = Carbon::now();
			$gate_log->updated_at = NULL;
			$gate_log->gate_in_date = Carbon::now();
			$gate_log->status_id = 1; //INITIATED
			$gate_log->save();

			$gate_log->number = 'VE' . $gate_log->id;
			$gate_log->save();

			//NEW VEHICLE GATE ENTRY DETAILS
			$vehile = new Vehicle;
			$vehile->fill($request->all());
			$vehile->company_id = $request->user()->company_id;
			$vehile->created_by_id = $request->user()->id;
			$vehile->created_at = Carbon::now();
			$vehile->updated_at = NULL;
			$vehile->save();

			//CREATE DIRECTORY TO STORAGE PATH
			$attachement_path = storage_path('app/public/gigo/gate_in/attachments/');
			Storage::makeDirectory($attachement_path, 0777);

			//SAVE VEHICLE PHOTO ATTACHMENT
			if (!empty($request->vehicle_photo)) {
				$file_name_with_extension = $request->vehicle_photo->getClientOriginalName();
				$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
				$extension = $request->vehicle_photo->getClientOriginalExtension();

				$name = $vehile->id . '_' . $file_name . '.' . $extension;

				$request->vehicle_photo->move(storage_path('app/public/gigo/gate_in/attachments/'), $name);
				$attachement = new Attachment;
				$attachement->attachment_of_id = 223; //NEED TO CONFIRM
				$attachement->attachment_type_id = 244; //NEED TO CONFIRM
				$attachement->entity_id = $vehile->id;
				$attachement->name = $name;
				$attachement->save();
			}

			//SAVE KM READING PHOTO
			if (!empty($request->km_reading_photo)) {
				$file_name_with_extension = $request->km_reading_photo->getClientOriginalName();
				$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
				$extension = $request->km_reading_photo->getClientOriginalExtension();

				$name = $vehile->id . '_' . $file_name . '.' . $extension;

				$request->km_reading_photo->move(storage_path('app/public/gigo/gate_in/attachments/'), $name);
				$attachement = new Attachment;
				$attachement->attachment_of_id = 223; //NEED TO CONFIRM
				$attachement->attachment_type_id = 244; //NEED TO CONFIRM
				$attachement->entity_id = $vehile->id;
				$attachement->name = $name;
				$attachement->save();
			}

			//SAVE DRIVER PHOTO
			if (!empty($request->driver_photo)) {
				$file_name_with_extension = $request->driver_photo->getClientOriginalName();
				$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
				$extension = $request->driver_photo->getClientOriginalExtension();

				$name = $vehile->id . '_' . $file_name . '.' . $extension;

				$request->driver_photo->move(storage_path('app/public/gigo/gate_in/attachments/'), $name);
				$attachement = new Attachment;
				$attachement->attachment_of_id = 223; //NEED TO CONFIRM
				$attachement->attachment_type_id = 244; //NEED TO CONFIRM
				$attachement->entity_id = $vehile->id;
				$attachement->name = $name;
				$attachement->save();
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Gate Entry Saved Successfully!!',
			], $this->success_code);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			], $this->success_code);
		}

	}

}
