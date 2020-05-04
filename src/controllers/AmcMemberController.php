<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\AmcMember;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class AmcMemberController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getAmcMemberList(Request $request) {
		$amc_members = AmcMember::withTrashed()

			->select([
				'amc_members.id',
				'amc_members.name',
				'amc_members.code',

				DB::raw('IF(amc_members.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('amc_members.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('amc_members.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('amc_members.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('amc_members.deleted_at');
				}
			})
		;

		return Datatables::of($amc_members)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($amc_member) {
				$status = $amc_member->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $amc_member->name;
			})
			->addColumn('action', function ($amc_member) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-amc_member')) {
					$output .= '<a href="#!/gigo-pkg/amc_member/edit/' . $amc_member->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-amc_member')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#amc_member-delete-modal" onclick="angular.element(this).scope().deleteAmcMember(' . $amc_member->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getAmcMemberFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$amc_member = new AmcMember;
			$action = 'Add';
		} else {
			$amc_member = AmcMember::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['amc_member'] = $amc_member;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveAmcMember(Request $request) {
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
					'unique:amc_members,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:amc_members,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$amc_member = new AmcMember;
				$amc_member->company_id = Auth::user()->company_id;
			} else {
				$amc_member = AmcMember::withTrashed()->find($request->id);
			}
			$amc_member->fill($request->all());
			if ($request->status == 'Inactive') {
				$amc_member->deleted_at = Carbon::now();
			} else {
				$amc_member->deleted_at = NULL;
			}
			$amc_member->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Amc Member Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Amc Member Updated Successfully',
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

	public function deleteAmcMember(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$amc_member = AmcMember::withTrashed()->where('id', $request->id)->forceDelete();
			if ($amc_member) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Amc Member Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getAmcMembers(Request $request) {
		$amc_members = AmcMember::withTrashed()
			->with([
				'amc-members',
				'amc-members.user',
			])
			->select([
				'amc_members.id',
				'amc_members.name',
				'amc_members.code',
				DB::raw('IF(amc_members.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('amc_members.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'amc_members' => $amc_members,
		]);
	}
}