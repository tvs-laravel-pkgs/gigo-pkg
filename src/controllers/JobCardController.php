<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\JobCard;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class JobCardController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getJobCardList(Request $request) {
		$job_cards = JobCard::withTrashed()

			->select([
				'job_cards.id',
				'job_cards.name',
				'job_cards.code',

				DB::raw('IF(job_cards.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('job_cards.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('job_cards.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('job_cards.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('job_cards.deleted_at');
				}
			})
		;

		return Datatables::of($job_cards)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($job_card) {
				$status = $job_card->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $job_card->name;
			})
			->addColumn('action', function ($job_card) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-job_card')) {
					$output .= '<a href="#!/gigo-pkg/job_card/edit/' . $job_card->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-job_card')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#job_card-delete-modal" onclick="angular.element(this).scope().deleteJobCard(' . $job_card->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getJobCardFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$job_card = new JobCard;
			$action = 'Add';
		} else {
			$job_card = JobCard::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['job_card'] = $job_card;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveJobCard(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Short Name is Required',
				'code.unique' => 'Short Name is already taken',
				'code.min' => 'Short Name is Minimum 3 Charachers',
				'code.max' => 'Short Name is Maximum 32 Charachers',
				'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:job_cards,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:job_cards,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$job_card = new JobCard;
				$job_card->company_id = Auth::user()->company_id;
			} else {
				$job_card = JobCard::withTrashed()->find($request->id);
			}
			$job_card->fill($request->all());
			if ($request->status == 'Inactive') {
				$job_card->deleted_at = Carbon::now();
			} else {
				$job_card->deleted_at = NULL;
			}
			$job_card->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Job Card Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Job Card Updated Successfully',
				]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function deleteJobCard(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$job_card = JobCard::withTrashed()->where('id', $request->id)->forceDelete();
			if ($job_card) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Job Card Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getJobCards(Request $request) {
		$job_cards = JobCard::withTrashed()
			->with([
				'job-cards',
				'job-cards.user',
			])
			->select([
				'job_cards.id',
				'job_cards.name',
				'job_cards.code',
				DB::raw('IF(job_cards.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('job_cards.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'job_cards' => $job_cards,
		]);
	}
}