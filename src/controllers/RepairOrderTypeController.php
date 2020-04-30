<?php

namespace Abs\GigoPkg;
use Abs\ApprovalPkg\ApprovalType;
use Abs\ApprovalPkg\EntityStatus;
use Abs\GigoPkg\JobCard;
use App\ActivityLog;
use App\Config;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class RepairOrderTypeController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.admin_theme');
	}

	public function getRepairOrderFilter() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getRepairOrderTypeList(Request $request) {
		dd('ff');
		$repair_order_type = RepairOrderType::withTrashed()
			->select([
				'repair_order_type.id',
				'repair_order_type.name',
				'repair_order_type.code',
			])
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('repair_order_type.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->code)) {
					$query->where('repair_order_type.code', 'LIKE', '%' . $request->code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('repair_order_type.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('repair_order_type.deleted_at');
				}
			})
			->where('repair_order_type.company_id', Auth::user()->company_id)
		;

		return Datatables::of($repair_order_type)
			->addColumn('name', function ($repair_order_type) {
				$status = $repair_order_type->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $repair_order_type->name;
			})
			->addColumn('action', function ($repair_order_type) {
				$img_edit = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img_edit_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_view = asset('public/themes/' . $this->data['theme'] . '/img/content/table/eye.svg');
				$img_view_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/eye-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-repair-order-type')) {
					$output .= '<a href="#!/gigo-pkg/repair-order-type/edit/' . $repair_order_type->id . '" id = "" title="Edit"><img src="' . $img_edit . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img_edit_active . '" onmouseout=this.src="' . $img_edit . '"></a>';
				}
				if (Entrust::can('view-repair-order-type')) {
					$output .= '<a href="#!/gigo-pkg/repair-order-type/view/' . $repair_order_type->id . '" id = "" title="View"><img src="' . $img_view . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img_view_active . '" onmouseout=this.src="' . $img_view . '"></a>';
				}
				if (Entrust::can('delete-repair-order-type')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_repair_order_type" onclick="angular.element(this).scope().deleteRepairOrderType(' . $repair_order_type->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>
					';
				}
				return $output;
			})
			->make(true);
	}

	public function getRepairOrderFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$repair_order_type = new RepairOrderType;
			$action = 'Add';
		} else {
			$repair_order_type = RepairOrderType::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['repair_order_type'] = $repair_order_type;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function getRepairOrderTypeView(Request $request) {
		$id = $request->id;
		$this->data['repair_order_type'] = $repair_order_type = RepairOrderType::withTrashed()->find($id);
		$this->data['action'] = 'View';
		return response()->json($this->data);
	}

	public function saveRepairOrder(Request $request) {
		dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Code is Required',
				'code.min' => 'Code is Minimum 3 Charachers',
				'code.max' => 'Code is Maximum 24 Charachers',
				'name.required' => 'Name is Required',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 64 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'required:true',
					'min:3',
					'max:64',
				],
				'code' => [
					'required:true',
					'min:2',
					'max:24',
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$repair_order = new RepairOrderType;
				$repair_order->created_by_id = Auth::user()->id;
				$repair_order->created_at = Carbon::now();
				$repair_order->updated_at = NULL;
			} else {
				$repair_order = RepairOrderType::withTrashed()->find($request->id);
				$repair_order->updated_by_id = Auth::user()->id;
				$repair_order->updated_at = Carbon::now();
			}
			$repair_order->fill($request->all());
			$repair_order->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$repair_order->deleted_at = Carbon::now();
				$repair_order->deleted_by_id = Auth::user()->id;
			} else {
				$repair_order->deleted_by_id = NULL;
				$repair_order->deleted_at = NULL;
			}
			$repair_order->save();
			
			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Repair Order Type Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Repair Order Type Updated Successfully',
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

	public function deleteRepairOrderType(Request $request) {
		DB::beginTransaction();
		try {
			$repair_order_type = RepairOrderType::withTrashed()->where('id', $request->id)->forceDelete();
			if ($repair_order_type) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Repair Order Type Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

}
