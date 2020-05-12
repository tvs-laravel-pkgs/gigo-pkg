<?php

namespace Abs\GigoPkg\Api;

use Abs\GigoPkg\RepairOrder;
use Abs\GigoPkg\ServiceOrderType;
use App\Address;
use App\Attachment;
use App\Config;
use App\Country;
use App\Customer;
use App\CustomerDetails;
use App\CustomerVoice;
use App\GateLog;
use App\Http\Controllers\Controller;
use App\JobOrder;
use App\JobOrderPart;
use App\JobOrderRepairOrder;
use App\Part;
use App\QuoteType;
use App\ServiceType;
use App\State;
use App\User;
use App\Vehicle;
use App\VehicleInspectionItemGroup;
use App\VehicleInventoryItem;
use App\VehicleModel;
use App\VehicleOwner;
use Auth;
use Carbon\Carbon;
use DB;
use File;
use Illuminate\Http\Request;
use Storage;
use Validator;

class VehicleInwardController extends Controller {
	public $successStatus = 200;

	public function getVehicleInwardList(Request $request) {
		try {
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

			$gate_log_ids = [];
			$gate_logs = GateLog::
				where('gate_logs.company_id', Auth::user()->company_id)
				->get();
			foreach ($gate_logs as $key => $gate_log) {
				if ($gate_log->status_id == 8120) {
					//Gate In Completed
					$gate_log_ids[] = $gate_log->id;
				} else {
// Others
					if ($gate_log->floor_adviser_id == $request->employee_id) {
						$gate_log_ids[] = $gate_log->id;
					}
				}
			}

			$vehicle_inward_list = GateLog::select('gate_logs.*')
				->with([
					'vehicleDetail',
					'vehicleDetail.vehicleOwner',
					'vehicleDetail.vehicleOwner.CustomerDetail',
				])
				->leftJoin('vehicles', 'gate_logs.vehicle_id', 'vehicles.id')
				->leftJoin('vehicle_owners', 'vehicles.id', 'vehicle_owners.vehicle_id')
				->leftJoin('customers', 'vehicle_owners.customer_id', 'customers.id')
				->whereIn('gate_logs.id', $gate_log_ids)
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
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}
	//JOB ORDER
	public function getJobOrderFormData($id) {
		try {
			$gate_log = GateLog::find($id);
			if (!$gate_log) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}

			$extras = [
				'job_order_types' => ServiceOrderType::getList(),
				'quote_types' => QuoteType::getList(),
				'service_types' => ServiceType::getList(),
				'reading_types' => Config::getConfigTypeList(33, 'name', '', true, 'Select Reading type'), //Reading types
			];

			//Job card details need to get future
			return response()->json([
				'success' => true,
				'gate_log' => $gate_log,
				'extras' => $extras,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveJobOrder(Request $request) {
		// dd($request->all());
		try {

			$validator = Validator::make($request->all(), [
				'gate_log_id' => [
					'required',
					'integer',
					'exists:gate_logs,id',
				],
				'driver_name' => [
					'required',
					'string',
					'max:191',
				],
				'mobile_number' => [
					'required',
					'min:10',
					'string',
				],
				'km_reading' => [
					'required',
					'numeric',
				],
				'reading_type_id' => [
					'required',
					'numeric',
					'exists:configs,id',
				],
				'type_id' => [
					'required',
					'numeric',
					'exists:service_order_types,id',
				],
				'quote_type_id' => [
					'required',
					'numeric',
					'exists:quote_types,id',
				],
				'service_type_id' => [
					'required',
					'numeric',
					'exists:service_types,id',
				],
				'outlet_id' => [
					'required',
					'numeric',
					'exists:outlets,id',
				],
				'contact_number' => [
					'nullable',
					'min:10',
					'max:10',
				],
				'driver_license_expiry_date' => [
					'nullable',
					'date',
				],
				'insurance_expiry_date' => [
					'nullable',
					'date',
				],
				'driver_license_attachment' => [
					'nullable',
					'mimes:jpeg,jpg,png',
				],
				'insuarance_attachment' => [
					'nullable',
					'mimes:jpeg,jpg,png',
				],
				'rc_book_attachment' => [
					'nullable',
					'mimes:jpeg,jpg,png',
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

			//GATE LOG UPDATE
			$gate_log = GateLog::find($request->gate_log_id);
			if (!$gate_log) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}
			$gate_log->driver_name = $request->driver_name;
			$gate_log->contact_number = $request->mobile_number;
			$gate_log->km_reading = $request->km_reading;
			$gate_log->reading_type_id = $request->reading_type_id;
			$gate_log->save();

			//JOB ORDER SAVE
			$job_order = JobOrder::firstOrNew([
				'gate_log_id' => $request->gate_log_id,
			]);
			$job_order->number = mt_rand(1, 10000);
			$job_order->fill($request->all());
			$job_order->company_id = Auth::user()->company_id;
			$job_order->updated_by_id = Auth::user()->id;
			$job_order->save();
			//dump($job_order->id);
			//Number Update
			$number = sprintf('%03' . 's', $job_order->id);
			$job_order->number = "JO-" . $number;
			$job_order->save();

			//CREATE DIRECTORY TO STORAGE PATH
			$attachement_path = storage_path('app/public/gigo/job_order/attachments/');
			Storage::makeDirectory($attachement_path, 0777);

			//SAVE DRIVER PHOTO ATTACHMENT
			if (!empty($request->driver_license_attachment)) {
				//REMOVE OLD ATTACHMENT
				$remove_previous_attachment = Attachment::where([
					'entity_id' => $job_order->id,
					'attachment_of_id' => 227, //Job Order
					'attachment_type_id' => 251, //Driver License
				])->first();
				if (!empty($remove_previous_attachment)) {
					$img_path = $attachement_path . $remove_previous_attachment->name;
					if (File::exists($img_path)) {
						File::delete($img_path);
					}
					$remove = $remove_previous_attachment->forceDelete();
				}

				$file_name_with_extension = $request->driver_license_attachment->getClientOriginalName();
				$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
				$extension = $request->driver_license_attachment->getClientOriginalExtension();

				$name = $job_order->id . '_' . $file_name . '.' . $extension;

				$request->driver_license_attachment->move($attachement_path, $name);
				$attachement = new Attachment;
				$attachement->attachment_of_id = 227; //Job Order
				$attachement->attachment_type_id = 251; //Driver License
				$attachement->entity_id = $job_order->id;
				$attachement->name = $name;
				$attachement->save();
			}
			//SAVE INSURANCE PHOTO ATTACHMENT
			if (!empty($request->insuarance_attachment)) {
				//REMOVE OLD ATTACHMENT
				$remove_previous_attachment = Attachment::where([
					'entity_id' => $job_order->id,
					'attachment_of_id' => 227, //Job Order
					'attachment_type_id' => 252, //Vehicle Insurance
				])->first();
				if (!empty($remove_previous_attachment)) {
					$img_path = $attachement_path . $remove_previous_attachment->name;
					if (File::exists($img_path)) {
						File::delete($img_path);
					}
					$remove = $remove_previous_attachment->forceDelete();
				}
				$file_name_with_extension = $request->insuarance_attachment->getClientOriginalName();
				$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
				$extension = $request->insuarance_attachment->getClientOriginalExtension();

				$name = $job_order->id . '_' . $file_name . '.' . $extension;

				$request->insuarance_attachment->move($attachement_path, $name);
				$attachement = new Attachment;
				$attachement->attachment_of_id = 227; //Job Order
				$attachement->attachment_type_id = 252; //Vehicle Insurance
				$attachement->entity_id = $job_order->id;
				$attachement->name = $name;
				$attachement->save();
			}
			//SAVE INSURANCE PHOTO ATTACHMENT
			if (!empty($request->rc_book_attachment)) {
				//REMOVE OLD ATTACHMENT
				$remove_previous_attachment = Attachment::where([
					'entity_id' => $job_order->id,
					'attachment_of_id' => 227, //Job Order
					'attachment_type_id' => 250, //RC Book
				])->first();
				if (!empty($remove_previous_attachment)) {
					$img_path = $attachement_path . $remove_previous_attachment->name;
					if (File::exists($img_path)) {
						File::delete($img_path);
					}
					$remove = $remove_previous_attachment->forceDelete();
				}
				$file_name_with_extension = $request->rc_book_attachment->getClientOriginalName();
				$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
				$extension = $request->rc_book_attachment->getClientOriginalExtension();

				$name = $job_order->id . '_' . $file_name . '.' . $extension;

				$request->rc_book_attachment->move($attachement_path, $name);
				$attachement = new Attachment;
				$attachement->attachment_of_id = 227; //Job Order
				$attachement->attachment_type_id = 250; //RC Book
				$attachement->entity_id = $job_order->id;
				$attachement->name = $name;
				$attachement->save();
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Job order saved successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//INVENTORY
	public function getInventoryFormData($id) {
		try {
			$gate_log = GateLog::find($id);
			if (!$gate_log) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}

			$extras = [
				'inventory_type_list' => VehicleInventoryItem::getInventoryList(),
			];

			return response()->json([
				'success' => true,
				'gate_log' => $gate_log,
				'extras' => $extras,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveInventoryItem(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}
			$items_validator = Validator::make($request->vehicle_inventory_items, [
				'inventory_item_id.*' => [
					'required:true',
					'numeric',
					'exists:vehicle_inventory_items,id',
				],
				'is_available.*' => [
					'required',
					'numeric',
				],
				'remarks.*' => [
					'nullable',
					'string',
				],

			]);
			if ($items_validator->fails()) {
				return response()->json(['success' => false, 'errors' => $items_validator->errors()->all()]);
			}

			$job_order = JobOrder::find($request->job_order_id);
			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Job order Not found!',
				]);
			}

			DB::beginTransaction();
			if (isset($request->vehicle_inventory_items) && count($request->vehicle_inventory_items) > 0) {

				$job_order->vehicleInventoryItem()->detach();
				//Inserting Inventory Items
				foreach ($request->vehicle_inventory_items as $key => $vehicle_inventory_item) {

					$job_order->vehicleInventoryItem()
						->attach($vehicle_inventory_item['inventory_item_id'],
							[
								'is_available' => $vehicle_inventory_item['is_available'],
								'remarks' => $vehicle_inventory_item['remarks'],
							]
						);
				}

			}
			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Vehicle inventory items added successfully',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//DMS CHECKLIST SAVE
	public function saveDmsCheckList(Request $request) {
		//dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'warranty_expiry_date' => [
					'nullable',
					'date_format:"d-m-Y',
				],
				'ewp_expiry_date' => [
					'nullable',
					'date_format:"d-m-Y',
				],
				'warranty_expiry_attachment' => [
					'nullable',
					'mimes:jpeg,jpg,png',
				],
				'ewp_expiry_attachment' => [
					'nullable',
					'mimes:jpeg,jpg,png',
				],
				'membership_attachment' => [
					'nullable',
					'mimes:jpeg,jpg,png',
				],
				'is_verified' => [
					'nullable',
					'numeric',
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
			$job_order = JobOrder::find($request->job_order_id);
			$job_order->warranty_expiry_date = $request->warranty_expiry_date;
			$job_order->ewp_expiry_date = $request->ewp_expiry_date;
			$job_order->save();
			//CREATE DIRECTORY TO STORAGE PATH
			$attachment_path = storage_path('app/public/gigo/job_order/attachments/');
			Storage::makeDirectory($attachment_path, 0777);
			//SAVE WARRANTY EXPIRY PHOTO ATTACHMENT
			if (!empty($request->warranty_expiry_attachment)) {
				$attachment = $request->warranty_expiry_attachment;
				$entity_id = $job_order->id;
				$attachment_of_id = 227; //Job order
				$attachment_type_id = 256; //Warranty Policy
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}
			if (!empty($request->ewp_expiry_attachment)) {
				$attachment = $request->ewp_expiry_attachment;
				$entity_id = $job_order->id;
				$attachment_of_id = 227; //Job order
				$attachment_type_id = 257; //EWP
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}
			if (!empty($request->membership_attachment)) {
				$attachment = $request->membership_attachment;
				$entity_id = $job_order->id;
				$attachment_of_id = 227; //Job order
				$attachment_type_id = 258; //AMC
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}
			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Vehicle DMS checklist added successfully',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}
	//ScheduleMaintenance Form Data

	public function scheduleMaintenanceGetList() {
		// dd($id);
		try {
			$part_details = Part::with([
				'uom',
				'taxCode',
			])->get();

			$labour_details = RepairOrder::with([
				'repairOrderType',
				'uom',
				'taxCode',
				'skillLevel',
			])->get();

			return response()->json([
				'success' => true,
				'part_details' => $part_details,
				'labour_details' => $labour_details,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveScheduleMaintenance(Request $request) {
		//dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'job_order_parts.*.part_id' => [
					'required:true',
					'numeric',
					'exists:parts,id',
				],
				'job_order_parts.*,qty' => [
					'required',
					'numeric',
				],
				'job_order_parts.*.split_order_type_id' => [
					'nullable',
					'numeric',
					'exists:split_order_types,id',
				],
				'job_order_parts.*.rate' => [
					'required',
					'numeric',
				],
				'job_order_parts.*.amount' => [
					'required',
					'numeric',
				],
				'job_order_parts.*.status_id' => [
					'required',
					'numeric',
					'exists:configs,id',
				],
				'job_order_parts.*.is_oem_recommended' => [
					'nullable',
					'numeric',
				],
				'job_order_repair_orders.*.repair_order_id' => [
					'required:true',
					'numeric',
					'exists:repair_orders,id',
				],
				'job_order_repair_orders.*.qty' => [
					'required',
					'numeric',
				],
				'job_order_repair_orders.*.split_order_type_id' => [
					'nullable',
					'numeric',
					'exists:split_order_types,id',
				],
				'job_order_repair_orders.*.amount' => [
					'required',
					'numeric',
				],
				'job_order_repair_orders.*.status_id' => [
					'required',
					'numeric',
					'exists:configs,id',
				],
				'job_order_repair_orders.*.is_oem_recommended' => [
					'nullable',
					'numeric',
				],
				'job_order_repair_orders.*.failure_date' => [
					'nullable',
					'date_format:d-m-Y',
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
			if (isset($request->job_order_parts) && count($request->job_order_parts) > 0) {
				//Inserting Job order parts
				//dd($request->job_order_parts);
				foreach ($request->job_order_parts as $key => $part) {
					//dd($part['part_id']);
					$job_order_part = new JobOrderPart();
					$job_order_part->fill($part);
					$job_order_part->job_order_id = $request->job_order_id;
					$job_order_part->split_order_type_id = NULL;
					$job_order_part->amount = $part['qty'] * $part['rate'];
					$job_order_part->status_id = 8200; //Customer Approval Pending
					$job_order_part->save();
				}
			}
			if (isset($request->job_order_repair_orders) && count($request->job_order_repair_orders) > 0) {
				//Inserting Job order repair orders
				foreach ($request->job_order_repair_orders as $key => $repair) {

					$job_order_repair_order = JobOrderRepairOrder::firstOrNew([
						'repair_order_id' => $repair['repair_order_id'],
						'job_order_id' => $request->job_order_id,
					]);
					$job_order_repair_order->fill($repair);
					$job_order_repair_order->job_order_id = $request->job_order_id;
					$job_order_repair_order->split_order_type_id = NULL;
					$job_order_repair_order->is_recommended_by_oem = 1;
					$job_order_repair_order->is_customer_approved = 0;
					$job_order_repair_order->status_id = 8180; //Customer Approval Pending
					$job_order_repair_order->save();
				}
			}
			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Schedule Maintenance added successfully',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//VEHICLE GET FORM DATA

	public function getVehicleFormData($id) {
		// dd($id);
		try {
			$gate_log_validate = GateLog::find($id);
			if (!$gate_log_validate) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}

			//UPDATE GATE LOG
			$gate_log = GateLog::where('id', $id)
				->update([
					'status_id' => 8121, //VEHICLE INWARD INPROGRESS
					'floor_adviser_id' => Auth::user()->entity_id,
					'updated_by_id' => Auth::user()->id,
				]);

			$gate_log_detail = GateLog::with(['vehicleDetail'])->find($id);

			$extras = [
				'registration_types' => [
					['id' => 0, 'name' => 'Unregistred'],
					['id' => 1, 'name' => 'Registred'],
				],
				'vehicle_models' => VehicleModel::getList(),
			];

			return response()->json([
				'success' => true,
				'gate_log_detail' => $gate_log_detail,
				'extras' => $extras,
			]);
			// return VehicleInward::saveVehicleGateInEntry($request);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//VEHICLE SAVE
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
						'error' => $error,
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
					'unique:vehicles,engine_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'chassis_number' => [
					'required',
					'min:10',
					'max:64',
					'string',
					'unique:vehicles,chassis_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'vin_number' => [
					'required',
					'min:17',
					'max:32',
					'string',
					'unique:vehicles,vin_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
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
			if ($request->is_registered != 1) {
				return response()->json([
					'success' => false,
					'error' => 'Unregistred Vehile Not allow!!',
				]);
			}

			//ONLY FOR REGISTRED VEHICLE
			$vehicle = Vehicle::firstOrNew([
				'company_id' => Auth::user()->company_id,
				'registration_number' => $request->registration_number,
			]);
			$vehicle->fill($request->all());
			$vehicle->status_id = 8141; //CUSTOMER NOT MAPED
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
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//CUSTOMER GET FORM DATA
	public function getCustomerFormData($id) {
		try {

			$gate_log_details = GateLog::with([
				'vehicleDetail',
				'vehicleDetail.vehicleOwner',
				'vehicleDetail.vehicleOwner.CustomerDetail',
				'vehicleDetail.vehicleOwner.CustomerDetail.primaryAddress',
				'vehicleDetail.vehicleOwner.CustomerDetail.primaryAddress.country',
				'vehicleDetail.vehicleOwner.CustomerDetail.primaryAddress.state',
				'vehicleDetail.vehicleOwner.CustomerDetail.primaryAddress.city',
			])->find($id);

			if (!$gate_log_details) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}

			$extras = [
				'country_list' => Country::getList(),
				'ownership_list' => Config::getConfigTypeList(39, 'id', '', true, 'Select Ownership'), //VEHICLE OWNERSHIP TYPES
			];

			return response()->json([
				'success' => true,
				'gate_log_details' => $gate_log_details,
				'extras' => $extras,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//GET STATE BASED COUNTRY
	public function getState($country_id) {
		$this->data = Country::getState($country_id);
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	//GET CITY BASED STATE
	public function getcity($state_id) {
		$this->data = State::getCity($state_id);
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	//CUSTOMER SAVE
	public function saveCustomer(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'name' => [
					'required',
					'min:3',
					'string',
					'max:255',
				],
				'mobile_no' => [
					'required',
					'min:10',
					'max:10',
				],
				'email' => [
					'nullable',
					'max:255',
					'string',
				],
				'address_line1' => [
					'required',
					'min:3',
					'max:255',
					'string',
				],
				'address_line2' => [
					'nullable',
					'max:255',
					'string',
				],
				'country_id' => [
					'required',
					'exists:countries,id',
					'integer',
				],
				'state_id' => [
					'required',
					'exists:states,id',
					'integer',
				],
				'city_id' => [
					'required',
					'exists:cities,id',
					'integer',
				],
				'pincode' => [
					'required',
					'min:6',
					'max:6',
				],
				'gst_number' => [
					'nullable',
					'min:15',
					'max:15',
				],
				'pan_number' => [
					'nullable',
					'min:10',
					'max:10',
				],
				'ownership_id' => [
					'required',
					'exists:configs,id',
					'integer',
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

			$gate_log = GateLog::with([
				'vehicleDetail',
				'vehicleDetail.vehicleOwner',
			])
				->find($request->gate_log_id);
 			
			//issue : vijay : Need to check gate log exist validation

			// dd($gate_log);
			// dd($gate_log->vehicleDetail->VehicleOwner->vehicle_id);
			if (!$gate_log->vehicleDetail->VehicleOwner) {
				$customer = new Customer;
				$customer_details = new CustomerDetails;
				$address = new Address;
				$vehicle_owner = new VehicleOwner;
			//issue : vijay : customer created_at save missing, vehicle owner created_at & created_by_id save missing
				$customer->created_by_id = Auth::user()->id;
			} else {
				$customer = Customer::find($gate_log->vehicleDetail->VehicleOwner->customer_id);
				$vehicle_owner = VehicleOwner::where('vehicle_id', $gate_log->vehicleDetail->VehicleOwner->vehicle_id)->first();
				$customer->updated_by_id = Auth::user()->id;
			//issue : vijay : customer updated_at save missing, vehicle owner updated_at & updated_by_id save missing
				$address = Address::where('address_of_id', 24)->where('entity_id', $gate_log->vehicleDetail->VehicleOwner->customer_id)->first();
			}
			$customer->code = rand(1, 10000);
			$customer->fill($request->all());
			$customer->company_id = Auth::user()->company_id;
			$customer->gst_number = $request->gst_number;
			$customer->save();
			$customer->code = 'CUS' . $customer->id;
			$customer->save();

			//SAVE VEHICLE OWNER
			$vehicle_owner->vehicle_id = $gate_log->vehicleDetail->id;
			$vehicle_owner->customer_id = $customer->id;
			$vehicle_owner->from_date = Carbon::now();
			$vehicle_owner->ownership_id = $request->ownership_id;
			$vehicle_owner->save();

			if (!$address) {
				$address = new Address;
			}
			$address->fill($request->all());
			$address->company_id = Auth::user()->company_id;
			$address->address_of_id = 24; //CUSTOMER
			$address->entity_id = $customer->id;
			$address->address_type_id = 40; //PRIMART ADDRESS
			$address->name = 'Primary Address';
			$address->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Vehicle Mapped with customer Successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//VOICE OF CUSTOMER(VOC) GET FORM DATA
	public function getVocFormData($id) {
		try {
			$gate_log_detail = GateLog::with([
				'jobOrder',
				'jobOrder.customerVoice',
			])
				->find($id);

			if (!$gate_log_detail) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}

			$VOC_list = CustomerVoice::where('company_id', Auth::user()->company_id)
				->get();

			return response()->json([
				'success' => true,
				'VOC_list' => $VOC_list,
				'gate_log_detail' => $gate_log_detail,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//VOICE OF CUSTOMER(VOC) SAVE
	public function saveVoc(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'exists:job_orders,id',
					'integer',
				],
				'customer_voice_id.*' => [
					'integer',
					'exists:customer_voices,id',
					'distinct',
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

			$job_order = JobOrder::find($request->job_order_id);

			$job_order->customerVoice()->sync([]);
			$job_order->customerVoice()->sync($request->customer_voice_id);

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'VOC Added Successfully',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//ROAD TEST OBSERVATION GET FORM DATA
	public function getRoadTestObservationFormData($id) {
		try {
			$gate_log_detail = GateLog::with([
				'jobOrder',
			])
				->find($id);

			if (!$gate_log_detail) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}
			$extras = [
				'road_test_by' => Config::getConfigTypeList(36, 'name', '', false, ''), //ROAD TEST DONE BY
				'user_list' => User::getList(),
			];

			return response()->json([
				'success' => true,
				'gate_log_detail' => $gate_log_detail,
				'extras' => $extras,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//ROAD TEST OBSERVATION SAVE
	public function saveRoadTestObservation(Request $request) {
		// dd($request->all());
		try {
			if ($request->road_test_done_by_id == 8101) {
				// EMPLOYEE
				$validator_road_test = Validator::make($request->all(), [
					'road_test_performed_by_id' => [
						'required',
						'exists:users,id',
						'integer',
					],
				]);
				if ($validator_road_test->fails()) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => $validator_road_test->errors()->all(),
					]);
				}
			}
			$validator = Validator::make($request->all(), [
				'is_road_test_required' => [
					'required',
					'integer',
					'max:1',
				],
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'road_test_done_by_id' => [
					'required',
					'exists:configs,id',
					'integer',
				],
				'road_test_report' => [
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

			$job_order = JobOrder::find($request->job_order_id);
			$job_order->is_road_test_required = $request->is_road_test_required;
			$job_order->road_test_done_by_id = $request->road_test_done_by_id;
			if ($request->road_test_done_by_id == 8101) {
				// EMPLOYEE
				$job_order->road_test_performed_by_id = $request->road_test_performed_by_id;
			}
			$job_order->road_test_report = $request->road_test_report;
			$job_order->updated_by_id = Auth::user()->id;
			$job_order->updated_at = Carbon::now();
			$job_order->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Road Test Observation Added Successfully',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//EXPERT DIAGNOSIS REPORT GET FORM DATA
	public function getExpertDiagnosisReportFormData($id) {
		try {
			$gate_log_detail = GateLog::with([
				'jobOrder',
			])
				->find($id);

			if (!$gate_log_detail) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}
			$extras = [
				'user_list' => User::getList(),
			];

			return response()->json([
				'success' => true,
				'gate_log_detail' => $gate_log_detail,
				'extras' => $extras,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//EXPERT DIAGNOSIS REPORT SAVE
	public function saveExpertDiagnosisReport(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'expert_diagnosis_report_by_id' => [
					'required',
					'exists:users,id',
					'integer',
				],
				'expert_diagnosis_report' => [
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

			$job_order = JobOrder::find($request->job_order_id);
			$job_order->expert_diagnosis_report = $request->expert_diagnosis_report;
			$job_order->expert_diagnosis_report_by_id = $request->expert_diagnosis_report_by_id;
			$job_order->updated_by_id = Auth::user()->id;
			$job_order->updated_at = Carbon::now();
			$job_order->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Expert Diagnosis Report Added Successfully',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//VEHICLE INSPECTION GET FORM DATA
	public function getVehicleInspectiongeFormData($id) {
		try {
			$gate_log_validate = GateLog::find($id);
			if (!$gate_log_validate) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}

			$vehicle_inspection_item_group = VehicleInspectionItemGroup::with([
				'VehicleInspectionItems',
			])
				->where('company_id', Auth::user()->company_id)
				->get();

			$extras = [
				'vehicle_inspection_result_status' => Config::getConfigTypeList(32, 'id', '', false, ''), //VEHICLE INSPECTION RESULTS
			];

			return response()->json([
				'success' => true,
				'vehicle_inspection_item_group' => $vehicle_inspection_item_group,
				'extras' => $extras,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//VEHICLE INSPECTION SAVE
	public function saveVehicleInspection(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'vehicle_inspection_groups.*.vehicle_inspection_item_id' => [
					'required',
					'exists:vehicle_inspection_items,id',
					'integer',
				],
				'vehicle_inspection_groups.*.vehicle_inspection_result_status_id' => [
					'required',
					'exists:configs,id',
					'integer',
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

			$job_order = jobOrder::find($request->job_order_id);
			if ($request->vehicle_inspection_groups) {
				$job_order->jobOrderVehicleInspectionItem()->sync([]);
				foreach ($request->vehicle_inspection_groups as $key => $vehicle_inspection_group) {
					// dd($vehicle_inspection_group['vehicle_inspection_item_id']);
					$job_order->jobOrderVehicleInspectionItem()->attach($vehicle_inspection_group['vehicle_inspection_item_id'],
						['status_id' => $vehicle_inspection_group['vehicle_inspection_result_status_id'],
						]);
				}
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Vehicle Inspection Added Successfully',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//ESTIMATE GET FORM DATA
	public function getEstimateFormData($id) {
		try {
			$gate_log_detail = GateLog::with([
				'jobOrder',
				'jobOrder.getEomRecomentation',
				'jobOrder.getAdditionalRotAndParts',
			])
				->find($id);

			$oem_recomentaion_labour_amount = 0;
			$additional_rot_and_parts_labour_amount = 0;
			if ($gate_log_detail->jobOrder->getEomRecomentation) {
				// dd($gate_log_detail->jobOrder->getEOMRecomentation);
				foreach ($gate_log_detail->jobOrder->getEomRecomentation as $oemrecomentation_labour) {
					if ($oemrecomentation_labour['is_recommended_by_oem'] == 1) {
						//SCHEDULED MAINTANENCE
						$oem_recomentaion_labour_amount += $oemrecomentation_labour['amount'];
					}
					if ($oemrecomentation_labour['is_recommended_by_oem'] == 0) {
						//ADDITIONAL ROT AND PARTS
						$additional_rot_and_parts_labour_amount += $oemrecomentation_labour['amount'];
					}
				}
			}

			$oem_recomentaion_part_amount = 0;
			$additional_rot_and_parts_part_amount = 0;
			if ($gate_log_detail->jobOrder->getAdditionalRotAndParts) {
				foreach ($gate_log_detail->jobOrder->getAdditionalRotAndParts as $oemrecomentation_labour) {
					if ($oemrecomentation_labour['is_oem_recommended'] == 1) {
						//SCHEDULED MAINTANENCE
						$oem_recomentaion_part_amount += $oemrecomentation_labour['amount'];
					}
					if ($oemrecomentation_labour['is_oem_recommended'] == 0) {
						//ADDITIONAL ROT AND PARTS
						$additional_rot_and_parts_part_amount += $oemrecomentation_labour['amount'];
					}
				}
			}
			//OEM RECOMENTATION LABOUR AND PARTS AND SUB TOTAL
			$gate_log_detail->oem_recomentation_labour_amount = $oem_recomentaion_labour_amount;
			$gate_log_detail->oem_recomentation_part_amount = $oem_recomentaion_part_amount;
			$gate_log_detail->oem_recomentation_sub_total = $oem_recomentaion_labour_amount + $oem_recomentaion_part_amount;

			//ADDITIONAL ROT & PARTS LABOUR AND PARTS AND SUB TOTAL
			$gate_log_detail->additional_rot_parts_labour_amount = $additional_rot_and_parts_labour_amount;
			$gate_log_detail->additional_rot_parts_part_amount = $additional_rot_and_parts_part_amount;
			$gate_log_detail->additional_rot_parts_sub_total = $additional_rot_and_parts_labour_amount + $additional_rot_and_parts_part_amount;

			//TOTAL ESTIMATE
			$gate_log_detail->total_estimate_labour_amount = $oem_recomentaion_labour_amount + $additional_rot_and_parts_labour_amount;
			$gate_log_detail->total_estimate_parts_amount = $oem_recomentaion_part_amount + $additional_rot_and_parts_part_amount;
			$gate_log_detail->total_estimate_amount = (($oem_recomentaion_labour_amount + $additional_rot_and_parts_labour_amount) + ($oem_recomentaion_part_amount + $additional_rot_and_parts_part_amount));

			return response()->json([
				'success' => true,
				'gate_log_detail' => $gate_log_detail,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//ESTIMATE SAVE
	public function saveEstimate(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'estimated_delivery_date' => [
					'required',
					// 'date_format:d/m/Y h:i A', //NOT ACCEPT THIS FORMAT
					'date_format:d-m-Y h:i A',
					'string',
				],
				//WAITING FOR CONFIRMATION -- NOT CONFIRMED
				'is_customer_agreed' => [
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

			$job_order = jobOrder::find($request->job_order_id);
			$job_order->estimated_delivery_date = $date = date('Y-m-d H:i', strtotime(str_replace('/', '-', $request->estimated_delivery_date)));
			$job_order->updated_by_id = Auth::user()->id;
			$job_order->updated_at = Carbon::now();
			$job_order->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Estimate Details Added Successfully',
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
