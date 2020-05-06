<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use Abs\GigoPkg\ServiceOrderType;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class ServiceOrderTypeController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getServiceOrderTypeFilterData() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getServiceOrderTypeList(Request $request) {
		$service_order_types = ServiceOrderType::withTrashed()
			->select([
				'service_order_types.id',
				'service_order_types.name',
				'service_order_types.code',
				DB::raw('IF(service_order_types.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('service_order_types.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->short_name)) {
					$query->where('service_order_types.code', 'LIKE', '%' . $request->short_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('service_order_types.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('service_order_types.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('service_order_types.deleted_at');
				}
			})
		;

		return Datatables::of($service_order_types)
			->addColumn('status', function ($service_order_types) {
				$status = $service_order_types->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indigator ' . $status . '"></span>' . $service_order_types->status;
			})
			->addColumn('action', function ($service_order_types) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-service-order-type')) {
					$output .= '<a href="#!/gigo-pkg/service-type/edit/' . $service_order_types->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-service-order-type')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#service-order-type-delete-modal" onclick="angular.element(this).scope().deleteServiceOrderType(' . $service_order_types->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getServiceOrderTypeFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$service_order_type = new ServiceOrderType;
			$action = 'Add';
		} else {
			$service_order_type = ServiceOrderType::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['service_order_type'] = $service_order_type;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveServiceOrderType(Request $request) {
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
					'unique:service_order_types,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:service_order_types,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$service_order_type = new ServiceOrderType;
				$service_order_type->created_by_id = Auth::user()->id;
				$service_order_type->created_at = Carbon::now();
				$service_order_type->updated_at = NULL;
			} else {
				$service_order_type = ServiceOrderType::withTrashed()->find($request->id);
				$service_order_type->updated_by_id = Auth::user()->id;
				$service_order_type->updated_at = Carbon::now();
			}
			$service_order_type->fill($request->all());
			$service_order_type->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$service_order_type->deleted_at = Carbon::now();
				$service_order_type->deleted_by_id = Auth::user()->id;
			} else {
				$service_order_type->deleted_by_id = NULL;
				$service_order_type->deleted_at = NULL;
			}
			$service_order_type->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Service Order Type Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Service Order Type Updated Successfully',
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

	public function deleteServiceOrderType(Request $request) {
		DB::beginTransaction();
		try {
			$service_type = ServiceOrderType::withTrashed()->where('id', $request->id)->forceDelete();
			if ($service_type) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Service Order Type Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	
}