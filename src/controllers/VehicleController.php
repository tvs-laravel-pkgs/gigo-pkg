<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\Vehicle;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class VehicleController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getVehicleList(Request $request) {
		$vehicles = Vehicle::withTrashed()

			->select([
				'vehicles.id',
				'vehicles.name',
				'vehicles.code',

				DB::raw('IF(vehicles.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicles.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('vehicles.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('vehicles.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('vehicles.deleted_at');
				}
			})
		;

		return Datatables::of($vehicles)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($vehicle) {
				$status = $vehicle->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $vehicle->name;
			})
			->addColumn('action', function ($vehicle) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-vehicle')) {
					$output .= '<a href="#!/gigo-pkg/vehicle/edit/' . $vehicle->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-vehicle')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#vehicle-delete-modal" onclick="angular.element(this).scope().deleteVehicle(' . $vehicle->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getVehicleFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$vehicle = new Vehicle;
			$action = 'Add';
		} else {
			$vehicle = Vehicle::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['vehicle'] = $vehicle;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveVehicle(Request $request) {
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
					'unique:vehicles,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:vehicles,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$vehicle = new Vehicle;
				$vehicle->company_id = Auth::user()->company_id;
			} else {
				$vehicle = Vehicle::withTrashed()->find($request->id);
			}
			$vehicle->fill($request->all());
			if ($request->status == 'Inactive') {
				$vehicle->deleted_at = Carbon::now();
			} else {
				$vehicle->deleted_at = NULL;
			}
			$vehicle->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Updated Successfully',
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

	public function deleteVehicle(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$vehicle = Vehicle::withTrashed()->where('id', $request->id)->forceDelete();
			if ($vehicle) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Vehicle Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getVehicles(Request $request) {
		$vehicles = Vehicle::withTrashed()
			->with([
				'vehicles',
				'vehicles.user',
			])
			->select([
				'vehicles.id',
				'vehicles.name',
				'vehicles.code',
				DB::raw('IF(vehicles.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicles.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'vehicles' => $vehicles,
		]);
	}
}