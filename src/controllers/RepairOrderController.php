<?php

namespace Abs\GigoPkg;
use Abs\ApprovalPkg\ApprovalType;
use Abs\ApprovalPkg\EntityStatus;
use Abs\GigoPkg\RepairOrderType;
use Abs\GigoPkg\RepairOrder;
use Abs\GigoPkg\TaxCode;
use Abs\EmployeePkg\SkillLevel;
use App\Config;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class RepairOrderController extends Controller {

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

	public function getRepairOrderList(Request $request) {
		$repair_orders = RepairOrder::withTrashed()
			->select([
				'repair_orders.id',
				'repair_order_types.short_name',
				'repair_orders.code',
				'repair_orders.alt_code',
				'repair_orders.name',
				'skill_levels.name as skill_name',
				'repair_orders.hours',
				'repair_orders.amount',
				'tax_codes.code as tax_code',
				DB::raw('IF(repair_orders.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->leftJoin('repair_order_types', 'repair_order_types.id', 'repair_orders.type_id')
			->leftJoin('skill_levels', 'skill_levels.id', 'repair_orders.skill_level_id')
			->leftJoin('tax_codes', 'tax_codes.id', 'repair_orders.tax_code_id')
			->where(function ($query) use ($request) {
				if (!empty($request->short_name)) {
					$query->where('repair_order_types.short_name', 'LIKE', '%' . $request->short_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('repair_orders.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('repair_orders.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('repair_orders.deleted_at');
				}
			})
			->where('repair_orders.company_id', Auth::user()->company_id)
		;

		return Datatables::of($repair_orders)
		    ->addColumn('status', function ($repair_orders) {
				$status = $repair_orders->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indigator ' . $status . '"></span>' . $repair_orders->status;
			})
			->addColumn('action', function ($repair_orders) {
				$img_edit = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img_edit_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-repair-order')) {
					$output .= '<a href="#!/gigo-pkg/repair-order/edit/' . $repair_orders->id . '" id = "" title="Edit"><img src="' . $img_edit . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img_edit_active . '" onmouseout=this.src="' . $img_edit . '"></a>';
				}
				if (Entrust::can('delete-repair-order')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_repair_order" onclick="angular.element(this).scope().deleteRepairOrder(' . $repair_orders->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>
					';
				}
				return $output;
			})
			->make(true);
	}

	public function getRepairOrderFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$repair_order = new RepairOrder;
			$action = 'Add';
		} else {
			$repair_order = RepairOrder::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['repair_order_type'] = RepairOrderType::select('id','short_name')->where('company_id',Auth::user()->company_id)->get();
		$this->data['skill_level'] = SkillLevel::select('id','name')->where('company_id',Auth::user()->company_id)->get();
		$this->data['tax_code'] = TaxCode::select('id','code')->where('company_id',Auth::user()->company_id)->get();
		$this->data['repair_order'] = $repair_order;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveRepairOrder(Request $request) {
		try {
			$error_messages = [
				'type_id.required' => 'Type is Required',
				'code.required' => 'DBM Code is Required',
				'code.unique' => 'DBM Code is already taken',
				'code.min' => 'DBM Code is Minimum 3 Charachers',
				'code.max' => 'DBM Code is Maximum 6 Charachers',
				'alt_code.required' => 'DMS Code is Required',
				'alt_code.unique' => 'DMS Code is already taken',
				'alt_code.min' => 'DMS Code is Minimum 3 Charachers',
				'alt_code.max' => 'DMS Code is Maximum 6 Charachers',
				'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 64 Charachers',
				'skill_level_id.required' => 'Skill Level is Required',
				'hours.required' => 'Hours is Required',
				'amount.required' => 'Amount is Required',
				'tax_code_id.required' => 'Tax Code is Required',
			];
			$validator = Validator::make($request->all(), [
				'type_id' => 'required',
				'code' => [
					'required:true',
					'min:3',
					'max:6',
					'unique:repair_orders,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'alt_code' => [
					'required:true',
					'min:3',
					'max:6',
					'unique:repair_orders,alt_code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:64',
					'unique:repair_orders,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'skill_level_id' => 'required',
				'hours' => 'required',
				'amount' => 'required',
				'tax_code_id' => 'required',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$repair_order = new RepairOrder;
				$repair_order->created_by_id = Auth::user()->id;
				$repair_order->created_at = Carbon::now();
				$repair_order->updated_at = NULL;
			} else {
				$repair_order = RepairOrder::withTrashed()->find($request->id);
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
					'message' => 'Repair Order Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Repair Order Updated Successfully',
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

	public function deleteRepairOrder(Request $request) {
		DB::beginTransaction();
		try {
			$repair_order_type = RepairOrder::withTrashed()->where('id', $request->id)->forceDelete();
			if ($repair_order_type) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Repair Order  Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

}
