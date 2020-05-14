<?php

namespace Abs\GigoPkg\Api;
use Abs\GigoPkg\JobCard;
use App\Attachment;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
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

	
}
