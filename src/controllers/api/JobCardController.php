<?php

namespace Abs\GigoPkg\Api;

use Abs\GigoPkg\Bay;
use Abs\GigoPkg\JobCard;
use Abs\GigoPkg\JobOrder;
use Abs\GigoPkg\JobOrderRepairOrder;
use Abs\GigoPkg\RepairOrderMechanic;
use Abs\GigoPkg\GatePass;
use Abs\GigoPkg\GatePassDetail;
use Abs\GigoPkg\GatePassItem;
use Abs\EmployeePkg\SkillLevel;
use Abs\GigoPkg\MechanicTimeLog;
use Abs\GigoPkg\RepairOrder;
use App\Attachment;
use App\Employee;
use App\Http\Controllers\Controller;
use App\Vendor;
use Auth;
use DB;
use File;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Storage;
use Validator;

class JobCardController extends Controller {
	public $successStatus = 200;

	public function getJobCardList(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				'floor_supervisor_id' => [
					'required',
					'exists:users,id',
					'integer',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			$job_card_list = Jobcard::select('job_cards.*', 'job_cards.status_id')
				->with([
					'jobOrder',
					'jobOrder.gateLog',
					'jobOrder.gateLog.vehicleDetail',
					'jobOrder.gateLog.vehicleDetail.vehicleCurrentOwner',
					'jobOrder.gateLog.vehicleDetail.vehicleCurrentOwner.CustomerDetail' => function ($query) use ($request) {
						if (!empty($request->search_key)) {
							//dd($request->search_key);
							$query->Where('customers.name', 'LIKE', '%' . $request->search_key . '%');
						}
					},
				])
				->leftJoin('job_orders', 'job_orders.id', 'job_cards.job_order_id')
				->leftJoin('gate_logs', 'gate_logs.id', 'job_orders.gate_log_id')
				->leftJoin('vehicles', 'gate_logs.vehicle_id', 'vehicles.id')
				->leftJoin('vehicle_owners', 'vehicles.id', 'vehicle_owners.vehicle_id')
				->join('customers', 'vehicle_owners.customer_id', 'customers.id')
			// ->leftJoin('customers as cus', function ($query) {
			// $query->on('cus.id', 'vehicle_owners.customer_id')
			// 	->orderBy('vehicle_owners.from_date','DESC')
			// 	->limit(1);
			// })

			/*->leftJoin('customers', function($join) {
					$join->on('vehicle_owners.customer_id', '=', 'customers.id')
					->orderBy('vehicle_owners1.from_date','DESC')
					->limit(1);
				})*/
			//->whereIn('job_cards.id', $jobcard_ids)
				->where(function ($query) use ($request) {
					if (!empty($request->search_key)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('customers.name', 'LIKE', '%' . $request->search_key . '%');
					}
				})
			//Floor Supervisor not Assigned =>8220
				->whereRaw("IF (job_cards.`status_id` = '8220', job_cards.`floor_supervisor_id` IS  NULL, job_cards.`floor_supervisor_id` = '" . $request->floor_supervisor_id . "')")
				->groupBy('job_cards.id')
				->get();

			return response()->json([
				'success' => true,
				'job_card_list' => $job_card_list,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveJobCard(Request $request) {
		//dd($request->all());
		try {

			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
				],
				'job_card_number' => [
					'required',
					'min:10',
					'integer',
				],
				'job_card_photo' => [
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
			//JOB Card SAVE
			$job_card = JobCard::firstOrNew([
				'job_order_id' => $request->job_order_id,
			]);
			$job_card->job_card_number = $request->job_card_number;
			//$job_card->outlet_id = 32;
			$job_card->status_id = 8220;
			$job_card->company_id = Auth::user()->company_id;
			$job_card->created_by = Auth::user()->id;
			$job_card->save();

			//CREATE DIRECTORY TO STORAGE PATH
			$attachement_path = storage_path('app/public/gigo/job_card/attachments/');
			Storage::makeDirectory($attachement_path, 0777);

			//SAVE Job Card ATTACHMENT
			if (!empty($request->job_card_photo)) {
				//REMOVE OLD ATTACHMENT
				$remove_previous_attachment = Attachment::where([
					'entity_id' => $job_card->id,
					'attachment_of_id' => 228, //Job Card
					'attachment_type_id' => 255, //Jobcard Photo
				])->first();
				if (!empty($remove_previous_attachment)) {
					$img_path = $attachement_path . $remove_previous_attachment->name;
					if (File::exists($img_path)) {
						File::delete($img_path);
					}
					$remove = $remove_previous_attachment->forceDelete();
				}

				$file_name_with_extension = $request->job_card_photo->getClientOriginalName();
				$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
				$extension = $request->job_card_photo->getClientOriginalExtension();

				$name = $job_card->id . '_' . $file_name . '.' . $extension;

				$request->job_card_photo->move($attachement_path, $name);
				$attachement = new Attachment;
				$attachement->attachment_of_id = 228; //Job Card
				$attachement->attachment_type_id = 255; //Jobcard Photo
				$attachement->entity_id = $job_card->id;
				$attachement->name = $name;
				$attachement->save();
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Job Card saved successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//BAY ASSIGNMENT
	public function getBayFormData($job_card_id) {
		try {
			$job_card = JobCard::find($job_card_id);
			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not Found!',
				]);
			}

			$bay_list = Bay::with([
				'status',
			])
				->where('outlet_id', $job_card->outlet_id)
				->get();
			$extras = [
				'bay_list' => $bay_list,
			];

			return response()->json([
				'success' => true,
				'job_card' => $job_card,
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

	public function saveBay(Request $request) {
		//dd($request->all());
		try {

			$validator = Validator::make($request->all(), [
				'job_card_id' => [
					'required',
					'integer',
					'exists:job_cards,id',
				],
				'bay_id' => [
					'required',
					'integer',
					'exists:bays,id',
				],
				'floor_supervisor_id' => [
					'required',
					'integer',
					'exists:users,id',
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

			$job_card = JobCard::find($request->job_card_id);
			if (!$job_card->jobOrder) {
				return response()->json([
					'success' => false,
					'error' => 'Job Order Not Found!',
				]);
			}
			$job_card->floor_supervisor_id = $request->floor_supervisor_id;
			$job_card->save();

			$bay = Bay::find($request->bay_id);
			$bay->job_order_id = $job_card->job_order_id;
			$bay->status_id = 8241; //Assigned
			$bay->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Bay assignment saved successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function LabourAssignmentFormData($jobcard_id) {
		try {
			//JOB Card
			$job_card = JobCard::with([

				'jobOrder',
				'jobOrder.JobOrderRepairOrders',
			])->find($jobcard_id);

			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Invalid Job Order!',
				]);
			}

			$employee_details = Employee::select('job_cards.job_order_id', 'employees.*', 'skill_levels.short_name as skill_level_name', 'attendance_logs.user_id as user_status')
				->leftJoin('attendance_logs', 'attendance_logs.user_id', 'employees.id')
				->leftJoin('skill_levels', 'skill_levels.id', 'employees.skill_level_id')
				->leftJoin('repair_orders', 'repair_orders.skill_level_id', 'skill_levels.id')
				->leftJoin('job_order_repair_orders', 'job_order_repair_orders.repair_order_id', 'repair_orders.id')
				->leftJoin('job_cards', 'job_cards.job_order_id', 'job_order_repair_orders.job_order_id')
			/*->leftJoin('employees', 'employees.deputed_outlet_id', 'job_cards.outlet_id')*/
				->where('job_cards.id', $jobcard_id)
				->where('employees.is_mechanic', 1)
			//->whereDate('attendance_logs.date', '=', Carbon::today())
				->get();

			return response()->json([
				'success' => true,
				'job_order_view' => $job_card,
				'employee_details' => $employee_details,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function LabourAssignmentFormSave(Request $request) {
		try {
			$items_validator = Validator::make($request->labour_details[0], [
				'job_order_repair_order_id' => [
					'required',
					'integer',
				],
			]);

			if ($items_validator->fails()) {
				return response()->json(['success' => false, 'errors' => $items_validator->errors()->all()]);
			}

			DB::beginTransaction();

			foreach ($request->labour_details as $key => $repair_orders) {
				$job_order_repair_order = JobOrderRepairOrder::find($repair_orders['job_order_repair_order_id']);
				if (!$job_order_repair_order) {
					return response()->json([
						'success' => false,
						'error' => 'Job order Repair Order Not found!',
					]);
				}
				foreach ($repair_orders as $key => $mechanic) {
					if (is_array($mechanic)) {
						$repair_order_mechanic = RepairOrderMechanic::firstOrNew([
							'job_order_repair_order_id' => $repair_orders['job_order_repair_order_id'],
							'mechanic_id' => $mechanic['mechanic_id'],
						]);
						$repair_order_mechanic->job_order_repair_order_id = $repair_orders['job_order_repair_order_id'];
						$repair_order_mechanic->mechanic_id = $mechanic['mechanic_id'];
						$repair_order_mechanic->status_id = 8060;
						$repair_order_mechanic->created_by_id = Auth::user()->id;
						if ($repair_order_mechanic->exists) {
							$repair_order_mechanic->updated_by_id = Auth::user()->id;
						}
						$repair_order_mechanic->save();
					}
				}

			}
			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Repair Order Mechanic added successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function VendorList(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				'vendor_code' => [
					'required',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}
			DB::beginTransaction();

			$VendorList = Vendor::where('code', 'LIKE', '%' . $request->vendor_code . '%')
				->where(function ($query) {
					$query->where('type_id', 121)
						->orWhere('type_id', 122);
				})->get();

			DB::commit();
			return response()->json([
				'success' => true,
				'Vendor_list' => $VendorList,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function VendorDetails($vendor_id) {
		try {
			$vendor_details = Vendor::find($vendor_id);

			if (!$vendor_details) {
				return response()->json([
					'success' => false,
					'error' => 'Vendor Details Not found!',
				]);
			}
			return response()->json([
				'success' => true,
				'vendor_details' => $vendor_details,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	// JOB CARD VIEW DATA
	public function getJobCardViewData(Request $request) {
		try {
			// dd($job_card_id);
			$job_card = JobCard::find($request->job_card_id);

			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Invalid Job Order!',
				]);
			}

			$job_order_repair_order_ids = RepairOrderMechanic::where('mechanic_id', $request->mechanic_id)
				->pluck('job_order_repair_order_id')
				->toArray();

			$job_order_repair_orders = JobOrderRepairOrder::with([
				'repairOrderMechanics',
				'repairOrderMechanics.mechanic',
				'repairOrderMechanics.status',
			])
				->where('job_order_id', $job_card->job_order_id)
				->whereIn('id', $job_order_repair_order_ids)
				->get();

			// dd($job_order_repair_orders);
			return response()->json([
				'success' => true,
				'job_card' => $job_card,
				'job_order_repair_orders' => $job_order_repair_orders,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function getLabourReviewData($id) {
		try {
			$job_card = JobCard::find($id);
			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not Found!',
				]);
			}

			$labour_review_data = JobCard::with([
				'jobOrder',
				'jobOrder.JobOrderRepairOrders',
				'jobOrder.JobOrderRepairOrders.status',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.mechanic',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.status',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.mechanicTimeLogs'
				/*=>function($q){
					$q->select(DB::raw('
						DATEDIFF(mechanic_time_logs.start_date_time,mechanic_time_logs.end_date_time) as duration'));
				}*/
				,
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.mechanicTimeLogs.status',

			])
				->find($job_card->id);

			$job_card_repair_order_details = $labour_review_data->jobOrder->JobOrderRepairOrders;
			//dd($job_card_repair_order_details);
			$total_duration = 0;
			if ($job_card_repair_order_details) {
				foreach ($job_card_repair_order_details as $key => $job_card_repair_order) {
					$duration = [];
					//$total_duration=0;
					if ($job_card_repair_order->repairOrderMechanics) {
						foreach ($job_card_repair_order->repairOrderMechanics as $key1 => $repair_order_mechanic) {
							if ($repair_order_mechanic->mechanicTimeLogs) {
								foreach ($repair_order_mechanic->mechanicTimeLogs as $key2 => $mechanic_time_log) {
									$time1 = strtotime($mechanic_time_log->start_date_time);
									$time2 = strtotime($mechanic_time_log->end_date_time);
									if ($time2 < $time1) {
										$time2 += 86400;
									}
									$duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

								}
							}
						}
						$total_duration = sum_mechanic_duration($duration);
						//dd($total_duration);
						$total_duration = date("H:i:s", strtotime($total_duration));
						$format_change = explode(':', $total_duration);
						$hour = $format_change[0] . 'h';
						$minutes = $format_change[1] . 'm';
						$seconds = $format_change[2] . 's';
						$job_card_repair_order['total_duration'] = $hour . ' ' . $minutes . ' ' . $seconds;
						unset($duration);
					} else {
						$job_card_repair_order['total_duration'] = '';
					}
				}
			}
			//dd($labour_review_data);
			return response()->json([
				'success' => true,
				'labour_review_data' => $labour_review_data,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function viewJobCard($job_card_id) {
		try {
			$job_card = JobCard::find($job_card_id);
			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not Found!',
				]);
			}

			$job_card_detail = JobCard::with([
				//JOB CARD RELATION
				'bay',
				'outlet',
				'company',
				'business',
				'sacCode',
				'model',
				'segment',
				'status',
				//JOB ORDER RELATION
				'jobOrder',
				'jobOrder.serviceOrederType',
				'jobOrder.quoteType',
				'jobOrder.serviceType',
				'jobOrder.roadTestDoneBy',
				'jobOrder.roadTestPreferedBy',
				'jobOrder.expertDiagnosisReportBy',
				'jobOrder.floorAdviser',
				'jobOrder.status',
				'jobOrder.jobOrderPart',
				'jobOrder.jobOrderPart.status',
				'jobOrder.jobOrderRepairOrder',
				'jobOrder.jobOrderRepairOrder.status',
				'jobOrder.customerVoice',
				'jobOrder.getEomRecomentation',
				'jobOrder.getAdditionalRotAndParts',
				'jobOrder.jobOrderRepairOrder.repairOrderMechanic',
				'jobOrder.jobOrderRepairOrder.repairOrderMechanic.mechanic',
				'jobOrder.jobOrderRepairOrder.repairOrderMechanic.status',
				'jobOrder.gateLog',
				'jobOrder.gateLog.vehicleDetail',
				'jobOrder.gateLog.vehicleDetail.vehicleCurrentOwner',
				'jobOrder.gateLog.vehicleDetail.vehicleCurrentOwner.CustomerDetail',
				'jobOrder.gateLog.vehicleDetail.vehicleCurrentOwner.ownerShipDetail',
				'jobOrder.gateLog.vehicleDetail.vehicleModel',
				'jobOrder.gateLog.driverAttachment',
				'jobOrder.gateLog.kmAttachment',
				'jobOrder.gateLog.vehicleAttachment',
				'jobOrder.vehicleInventoryItem',
				'jobOrder.jobOrderVehicleInspectionItem',
			])
				->find($job_card_id);

			//GET OEM RECOMENTATION AND ADDITIONAL ROT & PARTS
			$oem_recomentaion_labour_amount = 0;
			$additional_rot_and_parts_labour_amount = 0;
			if (!empty($job_card_detail->jobOrder->getEomRecomentation)) {
				// dd($job_card_detail->jobOrder->getEOMRecomentation);
				foreach ($job_card_detail->jobOrder->getEomRecomentation as $oemrecomentation_labour) {
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
			if (!empty($job_card_detail->jobOrder->getAdditionalRotAndParts)) {
				foreach ($job_card_detail->jobOrder->getAdditionalRotAndParts as $oemrecomentation_labour) {
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
			$job_card_detail->oem_recomentation_labour_amount = $oem_recomentaion_labour_amount;
			$job_card_detail->oem_recomentation_part_amount = $oem_recomentaion_part_amount;
			$job_card_detail->oem_recomentation_sub_total = $oem_recomentaion_labour_amount + $oem_recomentaion_part_amount;

			//ADDITIONAL ROT & PARTS LABOUR AND PARTS AND SUB TOTAL
			$job_card_detail->additional_rot_parts_labour_amount = $additional_rot_and_parts_labour_amount;
			$job_card_detail->additional_rot_parts_part_amount = $additional_rot_and_parts_part_amount;
			$job_card_detail->additional_rot_parts_sub_total = $additional_rot_and_parts_labour_amount + $additional_rot_and_parts_part_amount;

			//TOTAL ESTIMATE
			$job_card_detail->total_estimate_labour_amount = $oem_recomentaion_labour_amount + $additional_rot_and_parts_labour_amount;
			$job_card_detail->total_estimate_parts_amount = $oem_recomentaion_part_amount + $additional_rot_and_parts_part_amount;
			$job_card_detail->total_estimate_amount = (($oem_recomentaion_labour_amount + $additional_rot_and_parts_labour_amount) + ($oem_recomentaion_part_amount + $additional_rot_and_parts_part_amount));

			$job_card_detail->gate_log_attachment_url = storage_path('app/public/gigo/gate_in/attachments/');

			return response()->json([
				'success' => true,
				'job_card_detail' => $job_card_detail,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function getJobCardTimeLog($job_card_id) {
		try {
			$job_card_time_log = JobCard::with([
				'status',
				'jobOrder',
				// 'jobOrder.gateLog',
				// 'jobOrder.gateLog.vehicleDetail',
				// 'jobOrder.gateLog.vehicleDetail.vehicleModel',
				'jobOrder.JobOrderRepairOrders',
				'jobOrder.JobOrderRepairOrders.status',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.mechanic',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.status',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.mechanicTimeLogs',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.mechanicTimeLogs.status',
			])
				->find($job_card_id);

			if (!$job_card_time_log) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not found!',
				]);
			}

			$total_duration = 0;
			$overall_total_duration = [];
			if (!empty($job_card_time_log->jobOrder->JobOrderRepairOrders)) {
				foreach ($job_card_time_log->jobOrder->JobOrderRepairOrders as $key => $job_card_repair_order) {
					$duration = [];
					$job_card_repair_order->assigned_to_employee_count = count($job_card_repair_order->repairOrderMechanics);
					if ($job_card_repair_order->repairOrderMechanics) {
						foreach ($job_card_repair_order->repairOrderMechanics as $key1 => $repair_order_mechanic) {
							if ($repair_order_mechanic->mechanicTimeLogs) {
								$duration_difference = []; //FOR START TIME AND END TIME DIFFERENCE
								foreach ($repair_order_mechanic->mechanicTimeLogs as $key2 => $mechanic_time_log) {
									$time1 = strtotime($mechanic_time_log->start_date_time);
									$time2 = strtotime($mechanic_time_log->end_date_time);
									if ($time2 < $time1) {
										$time2 += 86400;
									}
									//PERTICULAR MECHANIC DATE
									$mechanic_time_log->date = date('d/m/Y', strtotime($mechanic_time_log->start_date_time));

									//PERTICULAR MECHANIC STATR TIME
									$mechanic_time_log->start_time = date('h:i:s a', strtotime($mechanic_time_log->start_date_time));

									//PERTICULAR MECHANIC END TIME
									$mechanic_time_log->end_time = date('h:i:s a', strtotime($mechanic_time_log->end_date_time));

									//TIME DURATION DIFFERENCE PERTICULAR MECHANIC DURATION
									$duration_difference[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

									//TOTAL DURATION FOR PARTICLUAR EMPLOEE
									$duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

									//OVERALL TOTAL WORKING DURATION
									$overall_total_duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

									$mechanic_time_log->duration_difference = sum_mechanic_duration($duration_difference);
									unset($duration_difference);
								}
							}
						}
						//TOTAL WORKING HOURS PER EMPLOYEE
						$total_duration = sum_mechanic_duration($duration);
						$total_duration = date("H:i:s", strtotime($total_duration));
						// dd($total_duration);
						$format_change = explode(':', $total_duration);
						$hour = $format_change[0] . 'h';
						$minutes = $format_change[1] . 'm';
						$seconds = $format_change[2] . 's';
						$job_card_repair_order['total_duration'] = $hour . ' ' . $minutes . ' ' . $seconds;
						unset($duration);
					} else {
						$job_card_repair_order['total_duration'] = '';
					}

				}
			}

			//OVERALL WORKING HOURS
			$overall_total_duration = sum_mechanic_duration($overall_total_duration);
			$overall_total_duration = date("H:i:s", strtotime($overall_total_duration));
			// dd($total_duration);
			$format_change = explode(':', $overall_total_duration);
			$hour = $format_change[0] . 'h';
			$minutes = $format_change[1] . 'm';
			$seconds = $format_change[2] . 's';
			$job_card_time_log->jobOrder['overall_total_duration'] = $hour . ' ' . $minutes . ' ' . $seconds;
			unset($overall_total_duration);

			$job_card_time_log->no_of_ROT = count($job_card_time_log->jobOrder->JobOrderRepairOrders);

			return response()->json([
				'success' => true,
				'job_card_time_log' => $job_card_time_log,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//Material GatePass Detail Save
	public function saveMaterialGatePassDetail(Request $request) {
		// dd($request->all());
		try {

			$validator = Validator::make($request->all(), [
				'job_card_id' => [
					'required',
					'integer',
					'exists:job_cards,id',
				],
				'vendor_type_id' => [
					'required',
					'integer',
					'exists:configs,id',
				],
				'vendor_id' => [
					'required',
					'integer',
					'exists:vendors,id',
				],
				'work_order_no' => [
					'required',
					'integer',
				],
				'work_order_description' => [
					'required',
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
			
			$job_card = JobCard::find($request->job_card_id);
			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not Found!',
				]);
			}

			$gate_pass = GatePass::firstOrNew([
				'job_card_id' => $request->job_card_id,
			]);
			$gate_pass->type_id = 8281;//Material Gate Pass
			// $gate_pass->company_id = Auth::user()->company_id;
			// $gate_pass->created_by = Auth::user()->id;
			$gate_pass->fill($request->all());
			$gate_pass->save();

			$gate_pass_detail = GatePassDetail::firstOrNew([
				'gate_pass_id' => $request->gate_pass_id,
				'work_order_no' => $request->work_order_no,
			]);
			$gate_pass_detail->vendor_type_id = $request->vendor_type_id;
			$gate_pass_detail->vendor_id = $request->vendor_id;
			$gate_pass_detail->vendor_contact_no = $request->vendor_contact_no;
			$gate_pass_detail->work_order_description = $request->work_order_description;
			// $gate_pass_detail->created_by = Auth::user()->id;
			$gate_pass_detail->save();
			
		DB::commit();

		return response()->json([
			'success' => true,
			'message' => 'Material Gate Pass Details Saved Successfully!!',
		]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//Material GatePass Item Save
	public function saveMaterialGatePassItem(Request $request) {
		// dd($request->all());
		try {

			$validator = Validator::make($request->all(), [
				'gate_pass_id' => [
					'required',
					'integer',
					'exists:gate_passes,id',
				],
				'item_details.*.item_description' => [
					'required',
					'min:3',
					'max:191',
				],
				'item_details.*.item_make' => [
					'required',
					'min:3',
					'max:191',
				],
				'item_details.*.item_model' => [
					'required',
					'min:3',
					'max:191',
				],
				'item_details.*.item_serial_no' => [
					'required',
					'min:3',
					'max:191',
				],
				'item_details.*.qty' => [
					'required',
				],
				'item_details.*.remarks' => [
					'required',
					'min:3',
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

			foreach($request->item_details as $key => $item_detail){
				$gate_pass_item = GatePassItem::firstOrNew([
					'gate_pass_id' => $request->gate_pass_id,
					'item_serial_no' => $item_detail['item_serial_no'],
				]);
				$gate_pass_item->fill($item_detail);
				$gate_pass_item->save();
			}			

			//CREATE DIRECTORY TO STORAGE PATH
			$attachment_path = storage_path('app/public/gigo/job_order/attachments/');
			Storage::makeDirectory($attachment_path, 0777);

			//SAVE MATERIAL OUTWARD ATTACHMENT
			if (!empty($request->material_outward_attachments)) {
				foreach($request->material_outward_attachments as $material_outward_attachment){
					// $attachment = $request->material_outward_attachment;
					$attachment = $material_outward_attachment;
					$entity_id = $gate_pass_item_id->id;
					$attachment_of_id = 231; //Material Gate Pass
					$attachment_type_id = 238; //Material Gate Pass
					saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
				}				
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Material Gate Pass Item Saved Successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	// JOB CARD VIEW Save
	public function saveJobCardView(Request $request) {
		//dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_repair_order_id' => [
					'required',
				],
				'machanic_id' => [
					'required',
				],
				'status_id' => [
					'required',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}
			$repair_order_mechanic = RepairOrderMechanic::where('job_order_repair_order_id',$request->job_repair_order_id)->where('mechanic_id',$request->machanic_id)->first();

			if (!$repair_order_mechanic) {
				return response()->json([
					'success' => false,
					'error' => 'Job Order Repair Order Mechanic Not Found!',
				]);
			}
            DB::beginTransaction();

            if($request->status_id == 8261 || $request->status_id == 8264)
              {
            	$mechanic_time_log = new MechanicTimeLog;
            	$mechanic_time_log->start_date_time = Carbon::now();
            	$mechanic_time_log->repair_order_mechanic_id = $repair_order_mechanic->id;
	            $mechanic_time_log->status_id = $request->status_id;
				$mechanic_time_log->created_by_id = Auth::user()->id;
				$mechanic_time_log->save();
               }
            else
            	{
            	$mechanic_time_log = MechanicTimeLog::where('repair_order_mechanic_id',$repair_order_mechanic->id)->whereNull('end_date_time')->update(['end_date_time' => Carbon::now(), 'status_id' => $request->status_id]);
            	}
             
             //Update Status
            $update_repair_order_mechanic = RepairOrderMechanic::where('id',$repair_order_mechanic->id)->where('mechanic_id',$request->machanic_id)->update(['status_id' => $request->status_id]);


            //Update all mechanic work in joborder repair order
            $mechanic_work =  RepairOrderMechanic::where('job_order_repair_order_id',$request->job_repair_order_id)->select('status_id')->get()->toArray();
            $mechanic_status = 0; $jobcard_status = 0;
            foreach ($mechanic_work as $key => $mechanic_work_status) {
            	if($mechanic_work_status['status_id'] != 8263)
            	{
            		$mechanic_status = $mechanic_status+1;
            	}
            	if($mechanic_work_status['status_id'] == 8261)
            	{
            		$jobcard_status = $jobcard_status+1;
            	}
            }
            if($mechanic_status == 0)
            $update_job_repair_order = JobOrderRepairOrder::where('id',$request->job_repair_order_id)->update(['status_id' => 8185]); //Work Complet
            else
            $update_job_repair_order = JobOrderRepairOrder::where('id',$request->job_repair_order_id)->update(['status_id' => 8183]); // Work inpro

            //Update JobCard
            $job_order_repair_order_details = JobOrderRepairOrder::select('job_order_id')->where('id',$request->job_repair_order_id)->first();
            if($jobcard_status != 0)
            $update_job_card = Jobcard::where('job_order_id',$job_order_repair_order_details->job_order_id)->update(['status_id' => 8221]);

            //Mechanic Log Activity
            $machanic_time_log_activity = MechanicTimeLog::where('repair_order_mechanic_id',$repair_order_mechanic->id)->get()->toArray();

            //Stat Date and time and end date and time
            $work_start_date_time = MechanicTimeLog::where('repair_order_mechanic_id',$repair_order_mechanic->id)->select('start_date_time')->orderby('id','ASC')->get()->toArray();
            $work_end_date_time = MechanicTimeLog::where('repair_order_mechanic_id',$repair_order_mechanic->id)->select('end_date_time')->orderby('id','DESC')->get()->toArray();
             
             //Estimation Working Hours
            $estimation_work_hours = RepairOrder::select('hours')
            ->leftJoin('job_order_repair_orders','job_order_repair_orders.repair_order_id','repair_orders.id')
            ->where('job_order_repair_orders.id',$request->job_repair_order_id)->get()->toArray();
            
            //Total Working hours of mechanic
            $mechanic_time_log_check = MechanicTimeLog::select('start_date_time','end_date_time')->where('repair_order_mechanic_id',$repair_order_mechanic->id)->get()->toArray();

               $total_hours = 0;
             foreach ($mechanic_time_log_check as $key => $mechanic_time_log_check){
             	$date1 = $mechanic_time_log_check['start_date_time'];
             	$date2 = $mechanic_time_log_check['end_date_time'];
             	$seconds = strtotime($date2) - strtotime($date1);
                $hours = $seconds / 60 / 60;
                $total_hours = $hours+$total_hours;
                 }

			DB::commit();
			return response()->json([
				'success' => true,
				'mechanic_time_log' => "Work Log Saved Successfully",
				'machanic_time_log_activity' => $machanic_time_log_activity,
				'work_status' => $request->status_id,
				'work_start_date_time' => $work_start_date_time[0],
				'work_end_date_time' => $work_end_date_time[0],
				'estimation_work_hours' => $estimation_work_hours,
				'total_working_hours' => round($total_hours),
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