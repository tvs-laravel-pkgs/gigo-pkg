<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\JobOrderPart;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class JobOrderPartController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getJobOrderPartList(Request $request) {
		$job_order_parts = JobOrderPart::withTrashed()

			->select([
				'job_order_parts.id',
				'job_order_parts.name',
				'job_order_parts.code',

				DB::raw('IF(job_order_parts.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('job_order_parts.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('job_order_parts.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('job_order_parts.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('job_order_parts.deleted_at');
				}
			})
		;

		return Datatables::of($job_order_parts)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($job_order_part) {
				$status = $job_order_part->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $job_order_part->name;
			})
			->addColumn('action', function ($job_order_part) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-job_order_part')) {
					$output .= '<a href="#!/gigo-pkg/job_order_part/edit/' . $job_order_part->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-job_order_part')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#job_order_part-delete-modal" onclick="angular.element(this).scope().deleteJobOrderPart(' . $job_order_part->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getJobOrderPartFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$job_order_part = new JobOrderPart;
			$action = 'Add';
		} else {
			$job_order_part = JobOrderPart::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['job_order_part'] = $job_order_part;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveJobOrderPart(Request $request) {
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
					'unique:job_order_parts,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:job_order_parts,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$job_order_part = new JobOrderPart;
				$job_order_part->company_id = Auth::user()->company_id;
			} else {
				$job_order_part = JobOrderPart::withTrashed()->find($request->id);
			}
			$job_order_part->fill($request->all());
			if ($request->status == 'Inactive') {
				$job_order_part->deleted_at = Carbon::now();
			} else {
				$job_order_part->deleted_at = NULL;
			}
			$job_order_part->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Job Order Part Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Job Order Part Updated Successfully',
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

	public function deleteJobOrderPart(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$job_order_part = JobOrderPart::withTrashed()->where('id', $request->id)->forceDelete();
			if ($job_order_part) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Job Order Part Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getJobOrderParts(Request $request) {
		$job_order_parts = JobOrderPart::withTrashed()
			->with([
				'job-order-parts',
				'job-order-parts.user',
			])
			->select([
				'job_order_parts.id',
				'job_order_parts.name',
				'job_order_parts.code',
				DB::raw('IF(job_order_parts.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('job_order_parts.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'job_order_parts' => $job_order_parts,
		]);
	}
}