<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\InsuranceMember;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class InsuranceMemberController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getInsuranceMemberList(Request $request) {
		$insurance_members = InsuranceMember::withTrashed()

			->select([
				'insurance_members.id',
				'insurance_members.name',
				'insurance_members.code',

				DB::raw('IF(insurance_members.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('insurance_members.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('insurance_members.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('insurance_members.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('insurance_members.deleted_at');
				}
			})
		;

		return Datatables::of($insurance_members)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($insurance_member) {
				$status = $insurance_member->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $insurance_member->name;
			})
			->addColumn('action', function ($insurance_member) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-insurance_member')) {
					$output .= '<a href="#!/gigo-pkg/insurance_member/edit/' . $insurance_member->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-insurance_member')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#insurance_member-delete-modal" onclick="angular.element(this).scope().deleteInsuranceMember(' . $insurance_member->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getInsuranceMemberFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$insurance_member = new InsuranceMember;
			$action = 'Add';
		} else {
			$insurance_member = InsuranceMember::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['insurance_member'] = $insurance_member;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveInsuranceMember(Request $request) {
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
					'unique:insurance_members,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:insurance_members,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$insurance_member = new InsuranceMember;
				$insurance_member->company_id = Auth::user()->company_id;
			} else {
				$insurance_member = InsuranceMember::withTrashed()->find($request->id);
			}
			$insurance_member->fill($request->all());
			if ($request->status == 'Inactive') {
				$insurance_member->deleted_at = Carbon::now();
			} else {
				$insurance_member->deleted_at = NULL;
			}
			$insurance_member->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Insurance Member Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Insurance Member Updated Successfully',
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

	public function deleteInsuranceMember(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$insurance_member = InsuranceMember::withTrashed()->where('id', $request->id)->forceDelete();
			if ($insurance_member) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Insurance Member Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getInsuranceMembers(Request $request) {
		$insurance_members = InsuranceMember::withTrashed()
			->with([
				'insurance-members',
				'insurance-members.user',
			])
			->select([
				'insurance_members.id',
				'insurance_members.name',
				'insurance_members.code',
				DB::raw('IF(insurance_members.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('insurance_members.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'insurance_members' => $insurance_members,
		]);
	}
}