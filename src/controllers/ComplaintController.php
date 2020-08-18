<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\Complaint;
use Abs\GigoPkg\ComplaintGroup;
use App\Http\Controllers\Controller;
use App\SubAggregate;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class ComplaintController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getComplaintFilterData() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		$this->data['complaint_group'] = collect(ComplaintGroup::select('id', 'code')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'code' => 'Select Complaint Group']);
		$this->data['sub_aggregate'] = SubAggregate::select('id', 'code')->get()->prepend(['id' => '', 'code' => 'Select Complaint Group']);
		return response()->json($this->data);
	}

	public function getComplaintList(Request $request) {
		$complaints = Complaint::withTrashed()

			->select([
				'complaints.id',
				'complaints.name',
				'complaints.code',
				// 'complaint_groups.code as group_code',
				'sub_aggregates.code as sub_aggregate_code',
				'complaints.hours',
				'complaints.kms',
				'complaints.months',
				DB::raw('IF(complaints.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->leftJoin('sub_aggregates', 'sub_aggregates.id', 'complaints.sub_aggregate_id')
		// ->leftJoin('complaint_groups', 'complaint_groups.id', 'complaints.group_id')
			->where('complaints.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->code)) {
					$query->where('complaints.code', 'LIKE', '%' . $request->code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('complaints.name', 'LIKE', '%' . $request->name . '%');
				}
			})
		/*
			->where(function ($query) use ($request) {
				if (!empty($request->group)) {
					$query->where('complaints.group_id', $request->group);
				}
			})
			*/

			->where(function ($query) use ($request) {
				if (!empty($request->sub_aggregate)) {
					$query->where('complaints.sub_aggregate_id', $request->sub_aggregate);
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->hour)) {
					$query->where('complaints.hours', $request->hour);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->kms)) {
					$query->where('complaints.kms', 'LIKE', '%' . $request->kms . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->month)) {
					$query->where('complaints.months', 'LIKE', '%' . $request->month . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('complaints.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('complaints.deleted_at');
				}
			})
		;

		return Datatables::of($complaints)
			->addColumn('status', function ($complaints) {
				$status = $complaints->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indigator ' . $status . '"></span>' . $complaints->status;
			})
			->addColumn('action', function ($complaints) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-complaint')) {
					$output .= '<a href="#!/gigo-pkg/complaint/edit/' . $complaints->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-complaint')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#complaint-delete-modal" onclick="angular.element(this).scope().deleteComplaint(' . $complaints->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getComplaintFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$complaint = new Complaint;
			$action = 'Add';
		} else {
			$complaint = Complaint::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['complaint_group'] = collect(ComplaintGroup::select('id', 'code')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'code' => 'Select Complaint Group']);
		$this->data['sub_aggregate'] = SubAggregate::select('id', 'code', 'name')->get()->prepend(['id' => '', 'name' => '', 'code' => 'Select Complaint Group']);
		$this->data['success'] = true;
		$this->data['complaint'] = $complaint;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveComplaint(Request $request) {
		//dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Code is Required',
				'code.unique' => 'Code is already taken',
				'code.min' => 'Code is Minimum 3 Charachers',
				'code.max' => 'Code is Maximum 32 Charachers',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
				// 'group_id.required' => 'Complaint Group is Required',
				// 'group_id.unique' => 'Complaint Group is already taken',
				'kms.max' => 'Kilometer is Maximum 10 Charachers',
				'hours.max' => 'Hours is Maximum 10 Charachers',
				'months.max' => 'Hours is Maximum 8 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:complaints,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:complaints,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				/*'group_id' => [
					'required:true',
					'unique:complaints,code,' . $request->id . ',id,group_id,' . $request->group_id . ',company_id,' . Auth::user()->company_id,
					'unique:complaints,name,' . $request->id . ',id,group_id,' . $request->group_id . ',company_id,' . Auth::user()->company_id,

				],*/
				'sub_aggregate_id' => [
					'required:true',
					'unique:complaints,code,' . $request->id . ',id,sub_aggregate_id,' . $request->sub_aggregate_id . ',company_id,' . Auth::user()->company_id,
					'unique:complaints,name,' . $request->id . ',id,sub_aggregate_id,' . $request->sub_aggregate_id . ',company_id,' . Auth::user()->company_id,

				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$complaint = new Complaint;
				$complaint->created_by_id = Auth::user()->id;
				$complaint->created_at = Carbon::now();
				$complaint->updated_at = NULL;
			} else {
				$complaint = Complaint::withTrashed()->find($request->id);
				$complaint->updated_by_id = Auth::user()->id;
				$complaint->updated_at = Carbon::now();
			}
			$complaint->company_id = Auth::user()->company_id;
			$complaint->fill($request->all());
			if ($request->status == 'Inactive') {
				$complaint->deleted_at = Carbon::now();
			} else {
				$complaint->deleted_at = NULL;
			}
			$complaint->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Complaint  Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Complaint  Updated Successfully',
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

	public function deleteComplaint(Request $request) {
		DB::beginTransaction();
		//dd($request->id);
		try {
			$complaint = Complaint::withTrashed()->where('id', $request->id)->forceDelete();
			if ($complaint) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Complaint Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

}