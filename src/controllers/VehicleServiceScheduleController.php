<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\SerialNumberGroup;
use App\VehicleServiceSchedule;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class VehicleServiceScheduleController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getVehicleServiceScheduleFilterData() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getVehicleServiceScheduleList(Request $request) {
		$vehicle_service_schedules = VehicleServiceSchedule::withTrashed()

			->select([
				'vehicle_service_schedules.id',
				'vehicle_service_schedules.name',
				'vehicle_service_schedules.code',

				DB::raw('IF(vehicle_service_schedules.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicle_service_schedules.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->code)) {
					$query->where('vehicle_service_schedules.code', 'LIKE', '%' . $request->code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('vehicle_service_schedules.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('vehicle_service_schedules.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('vehicle_service_schedules.deleted_at');
				}
			})
		;

		return Datatables::of($vehicle_service_schedules)
			->addColumn('status', function ($vehicle_service_schedules) {
				$status = $vehicle_service_schedules->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indigator ' . $status . '"></span>' . $vehicle_service_schedules->status;
			})
			->addColumn('action', function ($vehicle_service_schedules) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$img_view = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$output = '';
				if (Entrust::can('view-vehicle-service-schedule')) {
					$output .= '<a href="#!/gigo-pkg/vehicle-service-schedule/edit/' . $vehicle_service_schedules->id . '" id = "" title="Edit"><img src="' . $img_view . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img_view . '" onmouseout=this.src="' . $img_view . '"></a>';
				}
				if (Entrust::can('edit-vehicle-service-schedule')) {
					$output .= '<a href="#!/gigo-pkg/vehicle-service-schedule/edit/' . $vehicle_service_schedules->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-vehicle-service-schedule')) {
					$output .= '<a href="javascript:void(0);" data-toggle="modal" data-target="#vehicle-service-schedule-delete-modal" onclick="angular.element(this).scope().deleteVehicleServiceSchedule(' . $vehicle_service_schedules->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getVehicleServiceScheduleFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$vehicle_service_schedule = new VehicleServiceSchedule;
			$action = 'Add';
		} else {
			$vehicle_service_schedule = VehicleServiceSchedule::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['vehicle_service_schedule'] = $vehicle_service_schedule;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveVehicleServiceSchedule(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				/*
					'code.required' => 'Code is Required',
					'code.min' => 'Code is Minimum 3 Charachers',
					'code.max' => 'Code is Maximum 32 Charachers',
				*/
				'code.unique' => 'Code is already taken',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
			];
			$validator = Validator::make($request->all(), [
				/*'code' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:vehicle_service_schedules,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],*/
				'name' => [
					'nullable',
					'min:3',
					'max:191',
					'unique:vehicle_service_schedules,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$vehicle_service_schedules = new VehicleServiceSchedule;
				$vehicle_service_schedules->created_by_id = Auth::user()->id;
				$vehicle_service_schedules->created_at = Carbon::now();
				$vehicle_service_schedules->updated_at = NULL;

				$result = SerialNumberGroup::generateNumber(VehicleServiceSchedule::$SERIAL_NUMBER_CATEGORY_ID);
				if ($result['success']) {
					$vehicle_service_schedules->code = $result['number'];
				} else {
					return [
						'success' => false,
						'errors' => $result['errors'],
					];
				}
			} else {
				$vehicle_service_schedules = VehicleServiceSchedule::withTrashed()->find($request->id);
				$vehicle_service_schedules->updated_by_id = Auth::user()->id;
				$vehicle_service_schedules->updated_at = Carbon::now();
			}
			$vehicle_service_schedules->company_id = Auth::user()->company_id;
			$vehicle_service_schedules->name = $request->name;
			if ($request->status == 'Inactive') {
				$vehicle_service_schedules->deleted_at = Carbon::now();
			} else {
				$vehicle_service_schedules->deleted_at = NULL;
			}
			$vehicle_service_schedules->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Service Schedule Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Service Schedule Updated Successfully',
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

	public function deleteVehicleServiceSchedule(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$vehicle_service_schedule = VehicleServiceSchedule::withTrashed()->where('id', $request->id)->forceDelete();
			if ($vehicle_service_schedule) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Vehicle Service Schedule Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

}