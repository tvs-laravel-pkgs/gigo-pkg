<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use Abs\GigoPkg\PauseWorkReason;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class PauseWorkReasonController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getPauseWorkReasonFilterData() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getPauseWorkReasonList(Request $request) {
		$pause_work_reason = PauseWorkReason::withTrashed()

			->select([
				'pause_work_reasons.id',
				'pause_work_reasons.name',
				DB::raw('IF(pause_work_reasons.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('pause_work_reasons.company_id', Auth::user()->company_id)
			
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('pause_work_reasons.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('pause_work_reasons.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('pause_work_reasons.deleted_at');
				}
			})
		;

		return Datatables::of($pause_work_reason)
			->addColumn('status', function ($pause_work_reason) {
				$status = $pause_work_reason->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indigator ' . $status . '"></span>' . $pause_work_reason->status;
			})
			->addColumn('action', function ($pause_work_reason) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-pause-work-reason')) {
					$output .= '<a href="#!/gigo-pkg/pause-work-reason/edit/' . $pause_work_reason->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-pause-work-reason')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#pause_work_reason-delete-modal" onclick="angular.element(this).scope().deletePauseWorkReason(' . $pause_work_reason->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getPauseWorkReasonFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$pause_work_reason = new PauseWorkReason;
			$action = 'Add';
		} else {
			$pause_work_reason = PauseWorkReason::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['pause_work_reason'] = $pause_work_reason;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function savePauseWorkReason(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'nullable',
					'min:3',
					'max:191',
					'unique:pause_work_reasons,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$pause_work_reason = new PauseWorkReason;
				$pause_work_reason->created_by_id = Auth::user()->id;
				$pause_work_reason->created_at = Carbon::now();
				$pause_work_reason->updated_at = NULL;
			} else {
				$pause_work_reason = PauseWorkReason::withTrashed()->find($request->id);
				$pause_work_reason->updated_by_id = Auth::user()->id;
				$pause_work_reason->updated_at = Carbon::now();
			}
			$pause_work_reason->fill($request->all());
			$pause_work_reason->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$pause_work_reason->deleted_at = Carbon::now();
				$pause_work_reason->deleted_by_id = Auth::user()->id;
			} else {
				$pause_work_reason->deleted_by_id = NULL;
				$pause_work_reason->deleted_at = NULL;
			}
			$pause_work_reason->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Pause Work Reason Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Pause Work Reason  Updated Successfully',
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

	public function deletePauseWorkReason(Request $request) {
		DB::beginTransaction();
		//dd($request->id);
		try {
			$pause_work_reason = PauseWorkReason::withTrashed()->where('id', $request->id)->forceDelete();
			if ($pause_work_reason) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Pause Work Reason Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	
}