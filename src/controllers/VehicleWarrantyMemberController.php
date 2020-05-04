<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\VehicleWarrantyMember;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class VehicleWarrantyMemberController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getVehicleWarrantyMemberList(Request $request) {
		$vehicle_warranty_members = VehicleWarrantyMember::withTrashed()

			->select([
				'vehicle_warranty_members.id',
				'vehicle_warranty_members.name',
				'vehicle_warranty_members.code',

				DB::raw('IF(vehicle_warranty_members.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicle_warranty_members.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('vehicle_warranty_members.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('vehicle_warranty_members.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('vehicle_warranty_members.deleted_at');
				}
			})
		;

		return Datatables::of($vehicle_warranty_members)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($vehicle_warranty_member) {
				$status = $vehicle_warranty_member->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $vehicle_warranty_member->name;
			})
			->addColumn('action', function ($vehicle_warranty_member) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-vehicle_warranty_member')) {
					$output .= '<a href="#!/gigo-pkg/vehicle_warranty_member/edit/' . $vehicle_warranty_member->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-vehicle_warranty_member')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#vehicle_warranty_member-delete-modal" onclick="angular.element(this).scope().deleteVehicleWarrantyMember(' . $vehicle_warranty_member->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getVehicleWarrantyMemberFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$vehicle_warranty_member = new VehicleWarrantyMember;
			$action = 'Add';
		} else {
			$vehicle_warranty_member = VehicleWarrantyMember::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['vehicle_warranty_member'] = $vehicle_warranty_member;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveVehicleWarrantyMember(Request $request) {
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
					'unique:vehicle_warranty_members,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:vehicle_warranty_members,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$vehicle_warranty_member = new VehicleWarrantyMember;
				$vehicle_warranty_member->company_id = Auth::user()->company_id;
			} else {
				$vehicle_warranty_member = VehicleWarrantyMember::withTrashed()->find($request->id);
			}
			$vehicle_warranty_member->fill($request->all());
			if ($request->status == 'Inactive') {
				$vehicle_warranty_member->deleted_at = Carbon::now();
			} else {
				$vehicle_warranty_member->deleted_at = NULL;
			}
			$vehicle_warranty_member->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Warranty Member Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Warranty Member Updated Successfully',
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

	public function deleteVehicleWarrantyMember(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$vehicle_warranty_member = VehicleWarrantyMember::withTrashed()->where('id', $request->id)->forceDelete();
			if ($vehicle_warranty_member) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Vehicle Warranty Member Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getVehicleWarrantyMembers(Request $request) {
		$vehicle_warranty_members = VehicleWarrantyMember::withTrashed()
			->with([
				'vehicle-warranty-members',
				'vehicle-warranty-members.user',
			])
			->select([
				'vehicle_warranty_members.id',
				'vehicle_warranty_members.name',
				'vehicle_warranty_members.code',
				DB::raw('IF(vehicle_warranty_members.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicle_warranty_members.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'vehicle_warranty_members' => $vehicle_warranty_members,
		]);
	}
}