<?php

namespace Abs\GigoPkg;

use App\Http\Controllers\Controller;
use Abs\GigoPkg\VehicleSegment;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class VehicleSegmentController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getVehicleSegmentFilter() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getVehicleSegmentList(Request $request) {
		$vehicle_segments = VehicleSegment::withTrashed()
			->select([
				'vehicle_segments.id',
				'vehicle_segments.code',
				'vehicle_segments.name',

				DB::raw('IF(vehicle_segments.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicle_segments.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->code)) {
					$query->where('vehicle_segments.code', 'LIKE', '%' . $request->code . '%');
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('vehicle_segments.name', 'LIKE', '%' . $request->name . '%');
				}
			})

			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('vehicle_segments.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('vehicle_segments.deleted_at');
				}
			})
		;

		return Datatables::of($vehicle_segments)

			->addColumn('status', function ($vehicle_segment) {
				$status = $vehicle_segment->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $vehicle_segment->status;
			})

			->addColumn('action', function ($vehicle_segment) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$action = '';
				if (Entrust::can('edit-vehicle-segment')) {
					$action .= '<a href="#!/gigo-pkg/vehicle-segment/edit/' . $vehicle_segment->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';

				}
				if (Entrust::can('delete-vehicle-segment')) {
					$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_vehicle_segment" onclick="angular.element(this).scope().deleteVehicleSegment(' . $vehicle_segment->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';

				}
				return $action;
			})
			->make(true);
	}

	public function getVehicleSegmentFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$vehicle_segment = new VehicleSegment;
			$action = 'Add';
		} else {
			$vehicle_segment = VehicleSegment::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['vehicle_segment'] = $vehicle_segment;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveVehicleSegment(Request $request) {
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
					'unique:vehicle_segments,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'nullable',
					'min:3',
					'max:191',
					'unique:vehicle_segments,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
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
				$vehicle_segment = new VehicleSegment;
				$vehicle_segment->created_by_id = Auth::user()->id;
			} else {
				$vehicle_segment = VehicleSegment::withTrashed()->find($request->id);
				$vehicle_segment->updated_by_id = Auth::user()->id;
			}
			$vehicle_segment->company_id = Auth::user()->company_id;
			
			$vehicle_segment->fill($request->all());
			if ($request->status == 'Inactive') {
				$vehicle_segment->deleted_at = Carbon::now();
				$vehicle_segment->deleted_by_id = Auth::user()->id;
			} else {
				$vehicle_segment->deleted_at = NULL;
				$vehicle_segment->deleted_by_id = NULL;
			}
			$vehicle_segment->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Segment Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Segment Updated Successfully',
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

	public function deleteVehicleSegment(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$vehicle_primary_application = VehicleSegment::withTrashed()->where('id', $request->id)->forceDelete();
			if ($vehicle_primary_application) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Vehicle Segment Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}

