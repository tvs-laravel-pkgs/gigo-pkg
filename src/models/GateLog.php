<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Attachment;
use App\Company;
use App\Config;
use App\Vehicle;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Storage;
use Validator;

class GateLog extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'gate_logs';
	public $timestamps = true;
	protected $fillable =
		["company_id", "number", "date", "driver_name", "contact_number", "vehicle_id", "km_reading", "reading_type_id", "gate_in_remarks", "gate_out_date", "gate_out_remarks", "gate_pass_id", "status_id"]
	;

	public function getDateOfJoinAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setDateOfJoinAttribute($date) {
		return $this->attributes['date_of_join'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public static function createFromObject($record_data) {

		$errors = [];
		$company = Company::where('code', $record_data->company)->first();
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company);
			return;
		}

		$admin = $company->admin();
		if (!$admin) {
			dump('Default Admin user not found');
			return;
		}

		$type = Config::where('name', $record_data->type)->where('config_type_id', 89)->first();
		if (!$type) {
			$errors[] = 'Invalid Tax Type : ' . $record_data->type;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'name' => $record_data->tax_name,
		]);
		$record->type_id = $type->id;
		$record->created_by_id = $admin->id;
		$record->save();
		return $record;
	}

	public static function getList($params = [], $add_default = true, $default_text = 'Select Gate Log') {
		$list = Collect(Self::select([
			'id',
			'name',
		])
				->orderBy('name')
				->get());
		if ($add_default) {
			$list->prepend(['id' => '', 'name' => $default_text]);
		}
		return $list;
	}

	public static function saveVehicleGateInEntry($request) {
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
					'required',
					'min:6',
					'string',
					'max:10',
					// 'unique:vehicles,registration_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'km_reading' => [
					'required',
					'numeric',
				],
				'driver_name' => [
					'required',
					'max:191',
					'min:3',
					'string',
				],
				'contact_number' => [
					'required',
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
			$attachement_path = storage_path('app/public/gigo/gate_in/attachments/');
			Storage::makeDirectory($attachement_path, 0777);

			//SAVE VEHICLE PHOTO ATTACHMENT
			if (!empty($request->vehicle_photo)) {
				$file_name_with_extension = $request->vehicle_photo->getClientOriginalName();
				$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
				$extension = $request->vehicle_photo->getClientOriginalExtension();

				$name = $gate_log->id . '_' . $file_name . '.' . $extension;

				$request->vehicle_photo->move(storage_path('app/public/gigo/gate_in/attachments/'), $name);
				$attachement = new Attachment;
				$attachement->attachment_of_id = 225; //GATE LOG
				$attachement->attachment_type_id = 247; //VEHICLE PHOTO
				$attachement->entity_id = $gate_log->id;
				$attachement->name = $name;
				$attachement->save();
			}

			//SAVE KM READING PHOTO
			if (!empty($request->km_reading_photo)) {
				$file_name_with_extension = $request->km_reading_photo->getClientOriginalName();
				$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
				$extension = $request->km_reading_photo->getClientOriginalExtension();

				$name = $gate_log->id . '_' . $file_name . '.' . $extension;

				$request->km_reading_photo->move(storage_path('app/public/gigo/gate_in/attachments/'), $name);
				$attachement = new Attachment;
				$attachement->attachment_of_id = 225; //GATE LOG
				$attachement->attachment_type_id = 248; //KM READING PHOTO
				$attachement->entity_id = $gate_log->id;
				$attachement->name = $name;
				$attachement->save();
			}

			//SAVE DRIVER PHOTO
			if (!empty($request->driver_photo)) {
				$file_name_with_extension = $request->driver_photo->getClientOriginalName();
				$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
				$extension = $request->driver_photo->getClientOriginalExtension();

				$name = $gate_log->id . '_' . $file_name . '.' . $extension;

				$request->driver_photo->move(storage_path('app/public/gigo/gate_in/attachments/'), $name);
				$attachement = new Attachment;
				$attachement->attachment_of_id = 225; //GATE LOG
				$attachement->attachment_type_id = 249; //DRIVER PHOTO
				$attachement->entity_id = $gate_log->id;
				$attachement->name = $name;
				$attachement->save();
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Gate Entry Saved Successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}

	}

}
