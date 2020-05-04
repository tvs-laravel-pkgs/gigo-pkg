<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\VehicleInspectionItem;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class VehicleInspectionItemController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getVehicleInspectionItemList(Request $request) {
		$vehicle_inspection_items = VehicleInspectionItem::withTrashed()

			->select([
				'vehicle_inspection_items.id',
				'vehicle_inspection_items.name',
				'vehicle_inspection_items.code',

				DB::raw('IF(vehicle_inspection_items.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicle_inspection_items.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('vehicle_inspection_items.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('vehicle_inspection_items.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('vehicle_inspection_items.deleted_at');
				}
			})
		;

		return Datatables::of($vehicle_inspection_items)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($vehicle_inspection_item) {
				$status = $vehicle_inspection_item->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $vehicle_inspection_item->name;
			})
			->addColumn('action', function ($vehicle_inspection_item) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-vehicle_inspection_item')) {
					$output .= '<a href="#!/gigo-pkg/vehicle_inspection_item/edit/' . $vehicle_inspection_item->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-vehicle_inspection_item')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#vehicle_inspection_item-delete-modal" onclick="angular.element(this).scope().deleteVehicleInspectionItem(' . $vehicle_inspection_item->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getVehicleInspectionItemFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$vehicle_inspection_item = new VehicleInspectionItem;
			$action = 'Add';
		} else {
			$vehicle_inspection_item = VehicleInspectionItem::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['vehicle_inspection_item'] = $vehicle_inspection_item;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveVehicleInspectionItem(Request $request) {
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
					'unique:vehicle_inspection_items,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:vehicle_inspection_items,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$vehicle_inspection_item = new VehicleInspectionItem;
				$vehicle_inspection_item->company_id = Auth::user()->company_id;
			} else {
				$vehicle_inspection_item = VehicleInspectionItem::withTrashed()->find($request->id);
			}
			$vehicle_inspection_item->fill($request->all());
			if ($request->status == 'Inactive') {
				$vehicle_inspection_item->deleted_at = Carbon::now();
			} else {
				$vehicle_inspection_item->deleted_at = NULL;
			}
			$vehicle_inspection_item->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Inspection Item Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Inspection Item Updated Successfully',
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

	public function deleteVehicleInspectionItem(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$vehicle_inspection_item = VehicleInspectionItem::withTrashed()->where('id', $request->id)->forceDelete();
			if ($vehicle_inspection_item) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Vehicle Inspection Item Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getVehicleInspectionItems(Request $request) {
		$vehicle_inspection_items = VehicleInspectionItem::withTrashed()
			->with([
				'vehicle-inspection-items',
				'vehicle-inspection-items.user',
			])
			->select([
				'vehicle_inspection_items.id',
				'vehicle_inspection_items.name',
				'vehicle_inspection_items.code',
				DB::raw('IF(vehicle_inspection_items.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicle_inspection_items.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'vehicle_inspection_items' => $vehicle_inspection_items,
		]);
	}
}