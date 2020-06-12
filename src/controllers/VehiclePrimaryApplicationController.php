<?php

namespace Abs\GigoPkg;

use App\Http\Controllers\Controller;
use Abs\GigoPkg\VehiclePrimaryApplication;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class VehiclePrimaryApplicationController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getVehiclePrimaryApplicationFilter() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getVehiclePrimaryApplicationList(Request $request) {
		$vehicle_primary_applications = VehiclePrimaryApplication::withTrashed()
			->select([
				'vehicle_primary_applications.id',
				'vehicle_primary_applications.code',
				'vehicle_primary_applications.name',

				DB::raw('IF(vehicle_primary_applications.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicle_primary_applications.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->code)) {
					$query->where('vehicle_primary_applications.code', 'LIKE', '%' . $request->code . '%');
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('vehicle_primary_applications.name', 'LIKE', '%' . $request->name . '%');
				}
			})

			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('vehicle_primary_applications.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('vehicle_primary_applications.deleted_at');
				}
			})
		;

		return Datatables::of($vehicle_primary_applications)

			->addColumn('status', function ($vehicle_primary_application) {
				$status = $vehicle_primary_application->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $vehicle_primary_application->status;
			})

			->addColumn('action', function ($vehicle_primary_application) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$action = '';
				if (Entrust::can('edit-vehicle-primary-application')) {
					$action .= '<a href="#!/gigo-pkg/vehicle-primary-application/edit/' . $vehicle_primary_application->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';

				}
				if (Entrust::can('delete-vehicle-primary-application')) {
					$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_vehicle_primary_application" onclick="angular.element(this).scope().deleteVehiclePrimaryApplication(' . $vehicle_primary_application->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';

				}
				return $action;
			})
			->make(true);
	}

	public function getVehiclePrimaryApplicationFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$vehicle_primary_application = new VehiclePrimaryApplication;
			$action = 'Add';
		} else {
			$vehicle_primary_application = VehiclePrimaryApplication::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['vehicle_primary_application'] = $vehicle_primary_application;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveVehiclePrimaryApplication(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Code is Required',
				'code.unique' => 'Code is already taken',
				'code.min' => 'Code is Minimum 3 Charachers',
				'code.max' => 'Code is Maximum 32 Charachers',
				// 'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:vehicle_primary_applications,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'nullable',
					'min:3',
					'max:191',
					'unique:vehicle_primary_applications,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$vehicle_primary_application = new VehiclePrimaryApplication;
				$vehicle_primary_application->created_by_id = Auth::user()->id;
			} else {
				$vehicle_primary_application = VehiclePrimaryApplication::withTrashed()->find($request->id);
				$vehicle_primary_application->updated_by_id = Auth::user()->id;
			}
			$vehicle_primary_application->company_id = Auth::user()->company_id;
			
			$vehicle_primary_application->fill($request->all());
			if ($request->status == 'Inactive') {
				$vehicle_primary_application->deleted_at = Carbon::now();
				$vehicle_primary_application->deleted_by_id = Auth::user()->id;
			} else {
				$vehicle_primary_application->deleted_at = NULL;
				$vehicle_primary_application->deleted_by_id = NULL;
			}
			$vehicle_primary_application->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Primary Application Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Primary Application Updated Successfully',
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

	public function deleteVehiclePrimaryApplication(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$vehicle_primary_application = VehiclePrimaryApplication::withTrashed()->where('id', $request->id)->forceDelete();
			if ($vehicle_primary_application) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Vehicle Primary Application Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
