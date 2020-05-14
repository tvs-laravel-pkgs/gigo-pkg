<?php

namespace Abs\GigoPkg\Api;

use Abs\GigoPkg\JobCard;
use Abs\GigoPkg\Bay;
use Abs\StatusPkg\Status;
use Abs\GigoPkg\JobOrder;
use Abs\GigoPkg\JobOrderRepairOrder;
use Abs\GigoPkg\RepairOrderMechanic;
use Abs\EmployeePkg\SkillLevel;
use App\Attachment;
use App\Employee;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use File;
use Illuminate\Http\Request;
use Storage;
use Validator;

class JobCardController extends Controller {
	public $successStatus = 200;

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
			$job_card->outlet_id = 32;
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

	public function LabourAssignmentFormData($jobcardid)
	{
		try {
			//JOB Card 
			$job_card = JobCard::with([
					'jobOrder',
					'jobOrder.JobOrderRepairOrders',
				])->find($jobcardid);

			if(!$job_card){
				return response()->json([
					'success' => false,
					'error' => 'Invalid Job Order!',
				]);
			}

			/*$get_employee_details = Employee::select('job_cards.job_order_id','employees.*','skill_levels.short_name as skill_level_name')
				->leftJoin('job_order_repair_orders', 'job_order_repair_orders.job_order_id', 'job_cards.job_order_id')
				->leftJoin('repair_orders', 'repair_orders.id', 'job_order_repair_orders.repair_order_id')
				->leftJoin('skill_levels', 'skill_levels.id', 'repair_orders.skill_level_id')
				->leftJoin('employees', 'employees.skill_level_id', 'skill_levels.id')
				->where('job_cards.job_order_id', $id)
				->where('employees.is_mechanic', 1)
				->get();*/

			$get_employee_details = Employee::select('job_cards.job_order_id','employees.*','skill_levels.short_name as skill_level_name')
			    ->leftJoin('skill_levels', 'skill_levels.id', 'employees.skill_level_id')
				->leftJoin('repair_orders', 'repair_orders.skill_level_id', 'skill_levels.id')
				->leftJoin('job_order_repair_orders', 'job_order_repair_orders.repair_order_id', 'repair_orders.id')
				->leftJoin('job_cards', 'job_cards.job_order_id', 'job_order_repair_orders.job_order_id')
				->where('job_cards.id', $jobcardid)
				->where('employees.is_mechanic', 1)
				->get();

			return response()->json([
				'success' => true,
				'job_order_view' => $job_card,
				'employee_details' => $get_employee_details,
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
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

}