<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\VehicleOwner;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class VehicleOwnerController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getVehicleOwnerList(Request $request) {
		$vehicle_owners = VehicleOwner::withTrashed()

			->select([
				'vehicle_owners.id',
				'vehicle_owners.name',
				'vehicle_owners.code',

				DB::raw('IF(vehicle_owners.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicle_owners.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('vehicle_owners.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('vehicle_owners.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('vehicle_owners.deleted_at');
				}
			})
		;

		return Datatables::of($vehicle_owners)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($vehicle_owner) {
				$status = $vehicle_owner->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $vehicle_owner->name;
			})
			->addColumn('action', function ($vehicle_owner) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-vehicle_owner')) {
					$output .= '<a href="#!/gigo-pkg/vehicle_owner/edit/' . $vehicle_owner->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-vehicle_owner')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#vehicle_owner-delete-modal" onclick="angular.element(this).scope().deleteVehicleOwner(' . $vehicle_owner->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getVehicleOwnerFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$vehicle_owner = new VehicleOwner;
			$action = 'Add';
		} else {
			$vehicle_owner = VehicleOwner::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['vehicle_owner'] = $vehicle_owner;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveVehicleOwner(Request $request) {
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
					'unique:vehicle_owners,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:vehicle_owners,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$vehicle_owner = new VehicleOwner;
				$vehicle_owner->company_id = Auth::user()->company_id;
			} else {
				$vehicle_owner = VehicleOwner::withTrashed()->find($request->id);
			}
			$vehicle_owner->fill($request->all());
			if ($request->status == 'Inactive') {
				$vehicle_owner->deleted_at = Carbon::now();
			} else {
				$vehicle_owner->deleted_at = NULL;
			}
			$vehicle_owner->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Owner Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Owner Updated Successfully',
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

	public function deleteVehicleOwner(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$vehicle_owner = VehicleOwner::withTrashed()->where('id', $request->id)->forceDelete();
			if ($vehicle_owner) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Vehicle Owner Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getVehicleOwners(Request $request) {
		$vehicle_owners = VehicleOwner::withTrashed()
			->with([
				'vehicle-owners',
				'vehicle-owners.user',
			])
			->select([
				'vehicle_owners.id',
				'vehicle_owners.name',
				'vehicle_owners.code',
				DB::raw('IF(vehicle_owners.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicle_owners.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'vehicle_owners' => $vehicle_owners,
		]);
	}
}