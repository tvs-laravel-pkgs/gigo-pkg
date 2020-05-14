<?php

namespace Abs\GigoPkg\Api;

use Abs\GigoPkg\Bay;
use Abs\StatusPkg\Status;
use Abs\GigoPkg\JobOrder;
use Abs\GigoPkg\JobOrderRepairOrder;
use Abs\GigoPkg\RepairOrderMechanic;
use Abs\EmployeePkg\SkillLevel;
use Abs\GigoPkg\JobCard;
use App\Attachment;
use App\Employee;
use App\Vendor;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use File;
use Illuminate\Http\Request;
use Storage;
use Validator;

class JobCardController extends Controller {
	public $successStatus = 200;

	public function getJobCardList(Request $request) {
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

			$jobcard_ids = [];
			$jobcards = Jobcard::
				where('jobcards.company_id', Auth::user()->company_id)
				->get();
			foreach ($jobcards as $key => $jobcard) {
				if ($jobcard->status_id == 8120) {
					//Gate In Completed
					$jobcard_ids[] = $jobcard->id;
				} else {
// Others
					if ($jobcard->floor_adviser_id == $request->employee_id) {
						$jobcard_ids[] = $jobcard->id;
					}
				}
			}

			$job_card_list = Jobcard::select('jobcards.*')
				->with([
					'vehicleDetail',
					'vehicleDetail.vehicleOwner',
					'vehicleDetail.vehicleOwner.CustomerDetail',
				])
				->leftJoin('vehicles', 'jobcards.vehicle_id', 'vehicles.id')
				->leftJoin('vehicle_owners', 'vehicles.id', 'vehicle_owners.vehicle_id')
				->leftJoin('customers', 'vehicle_owners.customer_id', 'customers.id')
				->whereIn('jobcards.id', $jobcard_ids)
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
			$bay->status_id = 8241;//Assigned
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

			$employee_details = Employee::select('job_cards.job_order_id','employees.*','skill_levels.short_name as skill_level_name','attendance_logs.user_id as user_status')
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

			foreach($request->labour_details as $key=>$repair_orders)
			{
				$job_order_repair_order = JobOrderRepairOrder::find($repair_orders['job_order_repair_order_id']);
					if (!$job_order_repair_order) {
					return response()->json([
						'success' => false,
						'error' => 'Job order Repair Order Not found!',
					]);
				    }
				    foreach ($repair_orders as $key => $mechanic) {
				    	if(is_array($mechanic))
				    	{
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

            $VendorList = Vendor::where('code','LIKE', '%' . $request->vendor_code . '%')
            ->where(function ($query) {
						$query->where('type_id',121)
							->orWhere('type_id',122);
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

	public function VendorDetails($vendor_id)
	{
		try {
			$vendor_details = Vendor::find($vendor_id);

			if(!$vendor_details){
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

			if(!$job_card){
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
		}catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}
}