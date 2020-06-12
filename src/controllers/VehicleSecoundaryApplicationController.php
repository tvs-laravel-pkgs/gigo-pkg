<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use Abs\GigoPkg\VehicleSecondaryApplication;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class VehicleSecoundaryApplicationController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getVehicleSecoundaryAppFilterData() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getVehicleSecoundaryAppList(Request $request) {
		$vehicle_secondary_applications = VehicleSecondaryApplication::withTrashed()

			->select([
				'vehicle_secondary_applications.id',
				'vehicle_secondary_applications.name',
				'vehicle_secondary_applications.code',

				DB::raw('IF(vehicle_secondary_applications.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicle_secondary_applications.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->short_name)) {
					$query->where('vehicle_secondary_applications.code', 'LIKE', '%' . $request->short_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('vehicle_secondary_applications.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('vehicle_secondary_applications.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('vehicle_secondary_applications.deleted_at');
				}
			})
		;

		return Datatables::of($vehicle_secondary_applications)
			 ->addColumn('status', function ($vehicle_secondary_applications) {
				$status = $vehicle_secondary_applications->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indigator ' . $status . '"></span>' . $vehicle_secondary_applications->status;
			})
			->addColumn('action', function ($vehicle_secondary_applications) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-vehicle-secoundary-application')) {
					$output .= '<a href="#!/gigo-pkg/vehicle-secoundary-application/edit/' . $vehicle_secondary_applications->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-vehicle-secoundary-application')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#vehicle-secoundary-application-delete-modal" onclick="angular.element(this).scope().deleteVehicleSecApp('.$vehicle_secondary_applications->id.')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getVehicleSecoundaryAppFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$vehicle_secondary_applications = new VehicleSecondaryApplication;
			$action = 'Add';
		} else {
			$vehicle_secondary_applications = VehicleSecondaryApplication::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['vehicle_secondary_applications'] = $vehicle_secondary_applications;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveVehicleSecoundaryApp(Request $request) {
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
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:vehicle_secondary_applications,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'nullable',
					'min:3',
					'max:191',
					'unique:vehicle_secondary_applications,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$vehicle_secondary_applications = new VehicleSecondaryApplication;
				$vehicle_secondary_applications->created_by_id = Auth::user()->id;
				$vehicle_secondary_applications->created_at = Carbon::now();
				$vehicle_secondary_applications->updated_at = NULL;
			} else {
				$vehicle_secondary_applications = VehicleSecondaryApplication::withTrashed()->find($request->id);
				$vehicle_secondary_applications->updated_by_id = Auth::user()->id;
				$vehicle_secondary_applications->updated_at = Carbon::now();
			}
			$vehicle_secondary_applications->company_id = Auth::user()->company_id;
			$vehicle_secondary_applications->fill($request->all());
			if ($request->status == 'Inactive') {
				$vehicle_secondary_applications->deleted_at = Carbon::now();
			} else {
				$vehicle_secondary_applications->deleted_at = NULL;
			}
			$vehicle_secondary_applications->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Secoundary Application Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Secoundary Application Updated Successfully',
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

	public function deleteVehicleSecoundaryApp(Request $request) {
		DB::beginTransaction();
		//dd($request->id);
		try {
			$vehicle_secondary_application = VehicleSecondaryApplication::withTrashed()->where('id', $request->id)->forceDelete();
			if ($vehicle_secondary_application) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Vehicle Secoundary Application Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	
}