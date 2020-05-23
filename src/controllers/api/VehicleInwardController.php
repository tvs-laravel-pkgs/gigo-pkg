<?php

namespace Abs\GigoPkg\Api;

use Abs\GigoPkg\RepairOrder;
use Abs\GigoPkg\ServiceOrderType;
use App\Address;
use App\Config;
use App\Country;
use App\Customer;
use App\CustomerVoice;
use App\EstimationType;
use App\GateLog;
use App\Http\Controllers\Controller;
use App\JobOrder;
use App\JobOrderPart;
use App\JobOrderRepairOrder;
use App\Part;
use App\QuoteType;
use App\RepairOrderType;
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
use Illuminate\Http\Request;
use Storage;
use Validator;

class VehicleInwardController extends Controller {
	public $successStatus = 200;

	public function getVehicleInwardList(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				//issue : vijay : business logic
				// 'floor_adviser_id' => [
				// 	'required',
				// 	'exists:employees,id',
				// 	'integer',
				// ],
				'offset' => 'nullable|numeric',
				'limit' => 'nullable|numeric',
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			$vehicle_inward_list_get = GateLog::
				//issue : not optimized
				// ->with([
				// 	'status',
				// 	'vehicle',
				// 	'vehicle.status',
				// 	'vehicle.currentOwner',
				// 	'vehicle.currentOwner.customer',
				// ])
				leftJoin('vehicles', 'gate_logs.vehicle_id', 'vehicles.id')
				->leftJoin('models', 'models.id', 'vehicles.model_id')
				->leftJoin('amc_members', 'amc_members.vehicle_id', 'vehicles.id')
				->leftJoin('amc_policies', 'amc_policies.id', 'amc_members.id')
				->join('configs as status', 'status.id', 'gate_logs.status_id')
				->select([
					'gate_logs.id',
					DB::raw('IF(vehicles.is_registered = 1,"Registered Vehicle","Un-Registered Vehicle") as registration_type'),
					'vehicles.registration_number',
					'models.model_number',
					'gate_logs.number',
					DB::raw('DATE_FORMAT(gate_logs.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(gate_logs.created_at,"%h:%i %p") as time'),
					'gate_logs.driver_name',
					'gate_logs.contact_number as driver_contact_number',
					DB::raw('GROUP_CONCAT(amc_policies.name) as amc_policies'),
					'status.name as status_name',
				])
				->where(function ($query) use ($request) {
					if (!empty($request->search_key)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('customers.name', 'LIKE', '%' . $request->search_key . '%');
					}
				})
			//Gate In Completed =>8120
				->where('gate_logs.status_id', 8120)
			// ->whereRaw("IF (`gate_logs`.`status_id` = '8120', `gate_logs`.`floor_adviser_id` IS  NULL, `gate_logs`.`floor_adviser_id` = '" . $request->floor_adviser_id . "')")
				->groupBy('gate_logs.id');
			//->get();

			$total_records = $vehicle_inward_list_get->get()->count();

			if ($request->offset) {
				$vehicle_inward_list_get->offset($request->offset);
			}
			if ($request->limit) {
				$vehicle_inward_list_get->limit($request->limit);
			}

			$gate_logs = $vehicle_inward_list_get->get();

			return response()->json([
				'success' => true,
				'gate_logs' => $gate_logs,
				'total_records' => $total_records,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}
	//VEHICLE INWARD VIEW DATA
	public function getVehicleInwardViewData(Request $r) {
		try {
			$gate_log = GateLog::with([
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'vehicle.currentOwner.customer',
				'vehicle.currentOwner.ownerShipDetail',
				'status',
				'driverAttachment',
				'kmAttachment',
				'vehicleAttachment',
			])
				->select([
					'gate_logs.*',
					DB::raw('DATE_FORMAT(gate_logs.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(gate_logs.created_at,"%h:%i %p") as time'),
				])
				->find($r->id);

			if (!$gate_log) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}

			//issue : repeated query and naming
			// $gate_log_detail = GateLog::with([
			// $gate_log = GateLog::with([
			// 	'status',
			// 	'driverAttachment',
			// 	'kmAttachment',
			// 	'vehicleAttachment',
			// 	'vehicle',
			// 	'vehicle.currentOwner.customer',
			// 	'vehicle.currentOwner.ownerShipDetail',
			// ])
			// 	->find($r->id);

			//Job card details need to get future
			return response()->json([
				'success' => true,
				'gate_log' => $gate_log,
				'attachement_path' => url('storage/app/public/gigo/gate_in/attachments/'),
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
		//dd($request->all());
		try {
			//issue : saravanan - Add max 10 rule for mobile number
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
					'max:10',
					'string',
				],
				'km_reading' => [
					'required',
					'numeric',
				],
				'reading_type_id' => [
					'required',
					'integer',
					'exists:configs,id',
				],
				'type_id' => [
					'required',
					'integer',
					'exists:service_order_types,id',
				],
				'quote_type_id' => [
					'required',
					'integer',
					'exists:quote_types,id',
				],
				'service_type_id' => [
					'required',
					'integer',
					'exists:service_types,id',
				],
				'outlet_id' => [
					'required',
					'integer',
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

			//issue : saravanan - created_by_id not saved
			//JOB ORDER SAVE
			$job_order = JobOrder::firstOrNew([
				'gate_log_id' => $request->gate_log_id,
				'company_id' => Auth::user()->company_id,
			]);
			$job_order->number = mt_rand(1, 10000);
			$job_order->fill($request->all());
			$job_order->company_id = Auth::user()->company_id;
			$job_order->save();
			if ($job_order->exists) {
				$job_order->updated_by_id = Auth::user()->id;
				$job_order->updated_at = Carbon::now();
			} else {
				$job_order->created_by_id = Auth::user()->id;
				$job_order->created_at = Carbon::now();
			}
			//dump($job_order->id);
			//Number Update
			$number = sprintf('%03' . 's', $job_order->id);
			$job_order->number = "JO-" . $number;
			$job_order->save();

			//issue : saravanan - save attachment code optimisation

			//CREATE DIRECTORY TO STORAGE PATH
			$attachment_path = storage_path('app/public/gigo/job_order/attachments/');
			Storage::makeDirectory($attachment_path, 0777);

			//SAVE DRIVER PHOTO ATTACHMENT
			if (!empty($request->driver_license_attachment)) {
				$attachment = $request->driver_license_attachment;
				$entity_id = $job_order->id;
				$attachment_of_id = 227; //Job order
				$attachment_type_id = 251; //Driver License
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}
			//SAVE INSURANCE PHOTO ATTACHMENT
			if (!empty($request->insuarance_attachment)) {
				$attachment = $request->insuarance_attachment;
				$entity_id = $job_order->id;
				$attachment_of_id = 227; //Job order
				$attachment_type_id = 252; //Vehicle Insurance
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}
			//SAVE INSURANCE PHOTO ATTACHMENT
			if (!empty($request->rc_book_attachment)) {
				$attachment = $request->rc_book_attachment;
				$entity_id = $job_order->id;
				$attachment_of_id = 227; //Job order
				$attachment_type_id = 250; //RC Book
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
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
			// issue : saravanan - use one get list function. Field type id condition missing
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
				'vehicle_inventory_items.*.inventory_item_id' => [
					'required',
					'numeric',
					'exists:vehicle_inventory_items,id',
				],
				'vehicle_inventory_items.*.is_available' => [
					'required',
					'numeric',
				],
				'vehicle_inventory_items.*.remarks' => [
					'nullable',
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
			$vehicle_inventory_items_count = count($request->vehicle_inventory_items);
			$vehicle_inventory_unique_items_count = count(array_unique(array_column($request->vehicle_inventory_items, 'inventory_item_id')));
			if ($vehicle_inventory_items_count != $vehicle_inventory_unique_items_count) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'message' => 'Inventory items are not unique',
				]);
			}

			//issue: saravanan - validations syntax wrong
			/*$items_validator = Validator::make($request->vehicle_inventory_items, [
				'inventory_item_id.*' => [
					'required',
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
			}*/

			$job_order = JobOrder::find($request->job_order_id);
			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Job order Not found!',
				]);
			}

			DB::beginTransaction();
			if (isset($request->vehicle_inventory_items) && count($request->vehicle_inventory_items) > 0) {
				//dd($request->vehicle_inventory_items);
				$job_order->vehicleInventoryItem()->detach();
				//Inserting Inventory Items
				foreach ($request->vehicle_inventory_items as $key => $vehicle_inventory_item) {
					$job_order->vehicleInventoryItem()
						->attach(
							$vehicle_inventory_item['inventory_item_id'],
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

			//issue : saravanan - created_at, created_by, updated_by & updated_at missing while save attachment
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

	public function getScheduleMaintenanceFormData() {
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

			$parts_rate = 0;
			$labour_amount = 0;
			$total_amount = 0;
			if ($labour_details) {
				foreach ($labour_details as $key => $labour) {
					$labour_amount += $labour->amount;
				}
			}
			if ($part_details) {
				foreach ($part_details as $key => $part) {
					$parts_rate += $part->rate;
				}
			}
			$total_amount = $parts_rate + $labour_amount;

			return response()->json([
				'success' => true,
				'part_details' => $part_details,
				'labour_details' => $labour_details,
				'total_amount' => number_format($total_amount, 2),
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
			//issue : saravanan - split_order_type_id, is_oem_recommended, status_id not required in job order parts requests. split_order_type_id, is_oem_recommended, status_id, failure_date not required in job order repair orders requests. also remove in validations
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'job_order_parts.*.part_id' => [
					'required:true',
					'integer',
					'exists:parts,id',
				],
				'job_order_parts.*.qty' => [
					'required',
					'numeric',
					'regex:/^\d+(\.\d{1,2})?$/',
				],
				'job_order_parts.*.rate' => [
					'required',
					'numeric',
					'regex:/^\d+(\.\d{1,2})?$/',
				],
				'job_order_repair_orders.*.repair_order_id' => [
					'required:true',
					'integer',
					'exists:repair_orders,id',
				],
				'job_order_repair_orders.*.qty' => [
					'required',
					'numeric',
					'regex:/^\d+(\.\d{1,2})?$/',
				],
				'job_order_repair_orders.*.amount' => [
					'required',
					'numeric',
					'regex:/^\d+(\.\d{1,2})?$/',
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
				//issue: saravanan - is_recommended_by_oem save missing. save default 1.
				foreach ($request->job_order_parts as $key => $part) {
					//dd($part['part_id']);
					$job_order_part = JobOrderPart::firstOrNew([
						'part_id' => $part['part_id'],
						'job_order_id' => $request->job_order_id,
					]);
					$job_order_part->fill($part);
					$job_order_part->job_order_id = $request->job_order_id;
					$job_order_part->split_order_type_id = NULL;
					$job_order_part->is_oem_recommended = 1;
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

//Addtional Rot & Part GetList

	public function addtionalRotPartGetList($id) {
		try {

			$job_order = JobOrder::find($id);
			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Job Order Not found!',
				]);
			}

			$part_details = JobOrderPart::with([
				'part',
				'part.uom',
				'part.taxCode',
				'splitOrderType',
				'status',
			])
				->where('job_order_id', $job_order->id)
				->get();

			$labour_details = JobOrderRepairOrder::with([
				'repairOrder',
				'repairOrder.repairOrderType',
				'repairOrder.uom',
				'repairOrder.taxCode',
				'repairOrder.skillLevel',
				'splitOrderType',
				'status',
			])
				->where('job_order_id', $job_order->id)
				->get();
			$parts_amount = 0;
			$labour_amount = 0;
			$total_amount = 0;

			//issue: relations naming
			if ($job_order->jobOrderRepairOrder) {
				foreach ($job_order->jobOrderRepairOrder as $key => $labour) {
					$labour_amount += $labour->amount;

				}
			}
			//issue: relations naming
			if ($job_order->jobOrderPart) {
				foreach ($job_order->jobOrderPart as $key => $part) {
					$parts_amount += $part->amount;

				}
			}
			$total_amount = $parts_amount + $labour_amount;

			return response()->json([
				'success' => true,
				'part_details' => $part_details,
				'labour_details' => $labour_details,
				'total_amount' => $total_amount,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveAddtionalRotPart(Request $request) {
		//dd($request->all());
		try {
			//issue : saravanan - split_order_type_id, is_oem_recommended, status_id not required in job order parts requests. split_order_type_id, is_oem_recommended, status_id, failure_date not required in job order repair orders requests. also remove in validations
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'job_order_parts.*.part_id' => [
					'required:true',
					'integer',
					'exists:parts,id',
				],
				'job_order_parts.*.qty' => [
					'required',
					'numeric',
					'regex:/^\d+(\.\d{1,2})?$/',
				],
				'job_order_parts.*.split_order_type_id' => [
					'nullable',
					'integer',
					'exists:split_order_types,id',
				],
				'job_order_parts.*.rate' => [
					'required',
					'numeric',
					'regex:/^\d+(\.\d{1,2})?$/',
				],
				'job_order_parts.*.status_id' => [
					'required',
					'integer',
					'exists:configs,id',
				],
				'job_order_parts.*.is_oem_recommended' => [
					'nullable',
					'numeric',
				],
				'job_order_repair_orders.*.repair_order_id' => [
					'required:true',
					'integer',
					'exists:repair_orders,id',
				],
				'job_order_repair_orders.*.qty' => [
					'required',
					'numeric',
					'regex:/^\d+(\.\d{1,2})?$/',
				],
				'job_order_repair_orders.*.split_order_type_id' => [
					'nullable',
					'integer',
					'exists:split_order_types,id',
				],
				'job_order_repair_orders.*.amount' => [
					'required',
					'numeric',
					'regex:/^\d+(\.\d{1,2})?$/',
				],
				'job_order_repair_orders.*.status_id' => [
					'required',
					'integer',
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
				//issue: saravanan - is_recommended_by_oem save missing. save default 0.
				foreach ($request->job_order_parts as $key => $part) {
					$job_order_part = JobOrderPart::firstOrNew([
						'part_id' => $part['part_id'],
						'job_order_id' => $request->job_order_id,
					]);
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
					$job_order_repair_order->is_recommended_by_oem = 0;
					$job_order_repair_order->is_customer_approved = 0;
					$job_order_repair_order->status_id = 8180; //Customer Approval Pending
					$job_order_repair_order->save();
				}
			}
			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Addtional Rot and Part added successfully',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}
	//Get Addtional Part Form Data
	public function getPartList($id) {
		try {
			$job_order = JobOrder::find($id);
			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Job Order Not Found!',
				]);
			}

			$extras = [
				'part_list' => Part::getList(),
			];

			return response()->json([
				'success' => true,
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
	//Get Addtional Rot Form Data
	public function getAddtionalRotFormData($id) {
		try {
			$job_order = JobOrder::find($id);
			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Job Order Not Found!',
				]);
			}
			$extras = [
				'rot_type_list' => RepairOrderType::getList(),
			];
			return response()->json([
				'success' => true,
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
	//Get Addtional Rot List
	public function getAddtionalRotList($id) {
		try {
			$repair_order_type = RepairOrderType::find($id);
			if (!$repair_order_type) {
				return response()->json([
					'success' => false,
					'error' => ' Repair order type not found!',
				]);
			}
			$rot_list = RepairOrder::roList($repair_order_type->id);

			$extras = [
				'rot_list' => $rot_list,
			];

			return response()->json([
				'success' => true,
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
	//Get Addtional Rot
	public function getRepairOrderData($id) {
		try {
			$repair_order = RepairOrder::find($id);
			if (!$repair_order) {
				return response()->json([
					'success' => false,
					'error' => ' Repair order not found!',
				]);
			}

			$repair_order_detail = RepairOrder::with([
				'repairOrderType',
				'uom',
				'taxCode',
				'skillLevel',
			])
				->where('id', $id)
				->get();

			return response()->json([
				'success' => true,
				'repair_order' => $repair_order_detail,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}

	}
	//Get Addtional Part
	public function getPartData($id) {
		try {
			$part = Part::find($id);
			if (!$part) {
				return response()->json([
					'success' => false,
					'error' => ' Part not found!',
				]);
			}
			$part_detail = Part::with([
				'uom',
				'taxCode',
			])
				->where('id', $id)
				->get();
			return response()->json([
				'success' => true,
				'part' => $part_detail,
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
			//issue: relation naming
			$gate_log_detail = GateLog::with(['vehicle'])->find($id);

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
					if ($error) {
						return response()->json([
							'success' => false,
							'error' => $error,
						]);
					}
				}
			}

			$validator = Validator::make($request->all(), [
				'is_registered' => [
					'required',
					'integer',
				],
				'registration_number' => [
					'required_if:is_registered,==,1',
					// 'min:8',
					// 'string',
					'max:10',
					'unique:vehicles,registration_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
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

			//issue: relation naming
			$gate_log_details = GateLog::with([
				'vehicle',
				'vehicle.currentOwner',
				'vehicle.currentOwner.customer',
				'vehicle.currentOwner.customer.primaryAddress',
				'vehicle.currentOwner.customer.primaryAddress.country',
				'vehicle.currentOwner.customer.primaryAddress.state',
				'vehicle.currentOwner.customer.primaryAddress.city',
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
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'gate_log_id' => [
					'required',
					'exists:gate_logs,id',
					'integer',
				],
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

			//issue: relation naming
			$gate_log = GateLog::with([
				'vehicle',
				'vehicle.vehicleOwner',
			])
				->find($request->gate_log_id);

			if (empty($gate_log)) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}
			//issue: relation naming
			//OWNERSHIP ALREADY EXIST OR NOT
			$vehicle_owners_exist = VehicleOwner::where([
				'vehicle_id' => $gate_log->vehicle->id,
				'ownership_id' => $request->ownership_id,
			])
				->first();

			if ($vehicle_owners_exist) {
				return response()->json([
					'success' => false,
					'message' => 'Ownership Alreay Taken in this Vehicle!',
				]);
			}

			$customer = Customer::firstOrNew([
				'name' => $request->name,
				'mobile_no' => $request->mobile_no,
			]);
			if ($customer->exists) {
				//FIRST
				$customer->updated_at = Carbon::now();
				$customer->updated_by_id = Auth::user()->id;

				$address = Address::where('address_of_id', 24)->where('entity_id', $customer->id)->first();
				//issue: relation naming
				$vehicle_owner = VehicleOwner::where([
					'vehicle_id' => $gate_log->vehicle->id,
					'customer_id' => $customer->id,
				])
					->first();
				// dd($vehicle_owner);
			} else {
				//NEW
				$customer->created_at = Carbon::now();
				$customer->created_by_id = Auth::user()->id;
				$vehicle_owner = new VehicleOwner;
				$address = new Address;
			}
			//issue : vijay : customer updated_at save missing, vehicle owner updated_at & updated_by_id save missing
			$customer->code = mt_rand(1, 1000);
			$customer->fill($request->all());
			$customer->company_id = Auth::user()->company_id;
			$customer->gst_number = $request->gst_number;
			$customer->save();
			$customer->code = 'CUS' . $customer->id;
			$customer->save();

			//issue: relation naming
			$vehicle_owner->vehicle_id = $gate_log->vehicle->id;
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
			DB::rollBack();
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
				'user_list' => User::getUserEmployeeList(),
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
			//issue: Vijay - No need for another validation for road_test_performed_by_id field.
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
				'user_list' => User::getUserEmployeeList(),
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
		DB::beginTransaction();
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
			//issue: relation naming
			$gate_log_detail = GateLog::with([
				'vehicle',
				'vehicle.model',
				'jobOrder',
				'jobOrder.getEomRecomentation',
				'jobOrder.getAdditionalRotAndParts',
			])
				->find($id);

			$oem_recomentaion_labour_amount = 0;
			$additional_rot_and_parts_labour_amount = 0;
			//issue: relation naming
			if ($gate_log_detail->jobOrder->getEomRecomentation) {
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
			//issue: relation naming
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
		DB::beginTransaction();
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
				//issue: is_customer_agreed - required
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

			$job_order = jobOrder::find($request->job_order_id);
			$job_order->estimated_delivery_date = $date = date('Y-m-d H:i', strtotime(str_replace('/', '-', $request->estimated_delivery_date)));
			$job_order->is_customer_agreed = $request->is_customer_agreed;
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

	// ESTIMATION DENIED GET FORM DATA
	public function getEstimationDeniedFormData($id) {
		// dd($id);
		try {
			$gate_log_validate = GateLog::find($id);
			if (!$gate_log_validate) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}
			//issue: select name & id - query optimisation.
			$estimation_type = EstimationType::where('company_id', Auth::user()->company_id)
				->get();

			return response()->json([
				'success' => true,
				'estimation_type' => $estimation_type,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//ESTIMATION DENIED SAVE
	public function saveEstimateDenied(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'estimation_type_id' => [
					'required',
					'integer',
					'exists:estimation_types,id',
				],
				'minimum_payable_amount' => [
					'required',
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

			$job_order = jobOrder::find($request->job_order_id);
			$job_order->estimation_type_id = $request->estimation_type_id;
			$job_order->minimum_payable_amount = $request->minimum_payable_amount;
			$job_order->updated_by_id = Auth::user()->id;
			$job_order->updated_at = Carbon::now();
			$job_order->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Estimation Denied Details Added Successfully',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//CUSTOMER CONFIRMATION SAVE
	public function saveCustomerConfirmation(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'gate_log_id' => [
					'required',
					'integer',
					'exists:gate_logs,id',
				],
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],

				//issue: is_customer_agreed - no need
				'is_customer_agreed' => [
					'required',
					'boolean',
				],

				'customer_photo' => [
					'required',
					'mimes:jpeg,jpg,png',
				],
				'customer_e_sign' => [
					'required',
					'mimes:jpeg,jpg,png',
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

			//UPDATE JOB ORDER REPAIR ORDER STATUS UPDATE
			//issue: readability
			$job_order_repair_order_status_update = JobOrderRepairOrder::where('job_order_id', $request->job_order_id)->update(['status_id' => 8181, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]); //MACHANIC NOT ASSIGNED

			//UPDATE JOB ORDER PARTS STATUS UPDATE
			//issue: readability
			$job_order_parts_status_update = JobOrderPart::where('job_order_id', $request->job_order_id)->update(['status_id' => 8201, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]); //NOT ISSUED

			// //UPDATE GATE LOG STATUS
			// $gate_log = GateLog::where('id', $request->gate_log_id)->update(['status_id', 8122, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]); //VEHICLE INWARD COMPLETED

			$attachment_path = storage_path('app/public/gigo/job_order/customer-confirmation/');
			Storage::makeDirectory($attachment_path, 0777);
			//SAVE WARRANTY EXPIRY PHOTO ATTACHMENT
			if (!empty($request->customer_photo)) {
				$attachment = $request->customer_photo;
				$entity_id = $request->job_order_id;
				$attachment_of_id = 227; //JOB ORDER
				$attachment_type_id = 254; //CUSTOMER SIGN PHOTO
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}
			if (!empty($request->customer_e_sign)) {
				$attachment = $request->customer_e_sign;
				$entity_id = $request->job_order_id;
				$attachment_of_id = 227; //JOB ORDER
				$attachment_type_id = 253; //CUSTOMER E SIGN
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}

			//GET TOTAL AMOUNT IN PARTS AND LABOUR
			$repair_order_and_parts_detils = $this->getEstimateFormData($request->gate_log_id);

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Vehicle Inwarded Successfully',
				'repair_order_and_parts_detils' => $repair_order_and_parts_detils,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//INITIATE NEW JOB
	public function saveInitiateJob(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'gate_log_id' => [
					'required',
					'integer',
					'exists:gate_logs,id',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			//UPDATE GATE LOG STATUS
			//issue: readability
			$gate_log = GateLog::where('id', $request->gate_log_id)->update(['status_id' => 8122, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]); //VEHICLE INWARD COMPLETED

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'JOB Initiated Successfully',
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
