<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\RepairOrderMechanic;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class RepairOrderMechanicController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getRepairOrderMechanicList(Request $request) {
		$repair_order_mechanics = RepairOrderMechanic::withTrashed()

			->select([
				'repair_order_mechanics.id',
				'repair_order_mechanics.name',
				'repair_order_mechanics.code',

				DB::raw('IF(repair_order_mechanics.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('repair_order_mechanics.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('repair_order_mechanics.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('repair_order_mechanics.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('repair_order_mechanics.deleted_at');
				}
			})
		;

		return Datatables::of($repair_order_mechanics)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($repair_order_mechanic) {
				$status = $repair_order_mechanic->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $repair_order_mechanic->name;
			})
			->addColumn('action', function ($repair_order_mechanic) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-repair_order_mechanic')) {
					$output .= '<a href="#!/gigo-pkg/repair_order_mechanic/edit/' . $repair_order_mechanic->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-repair_order_mechanic')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#repair_order_mechanic-delete-modal" onclick="angular.element(this).scope().deleteRepairOrderMechanic(' . $repair_order_mechanic->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getRepairOrderMechanicFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$repair_order_mechanic = new RepairOrderMechanic;
			$action = 'Add';
		} else {
			$repair_order_mechanic = RepairOrderMechanic::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['repair_order_mechanic'] = $repair_order_mechanic;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveRepairOrderMechanic(Request $request) {
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
					'unique:repair_order_mechanics,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:repair_order_mechanics,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$repair_order_mechanic = new RepairOrderMechanic;
				$repair_order_mechanic->company_id = Auth::user()->company_id;
			} else {
				$repair_order_mechanic = RepairOrderMechanic::withTrashed()->find($request->id);
			}
			$repair_order_mechanic->fill($request->all());
			if ($request->status == 'Inactive') {
				$repair_order_mechanic->deleted_at = Carbon::now();
			} else {
				$repair_order_mechanic->deleted_at = NULL;
			}
			$repair_order_mechanic->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Repair Order Mechanic Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Repair Order Mechanic Updated Successfully',
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

	public function deleteRepairOrderMechanic(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$repair_order_mechanic = RepairOrderMechanic::withTrashed()->where('id', $request->id)->forceDelete();
			if ($repair_order_mechanic) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Repair Order Mechanic Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getRepairOrderMechanics(Request $request) {
		$repair_order_mechanics = RepairOrderMechanic::withTrashed()
			->with([
				'repair-order-mechanics',
				'repair-order-mechanics.user',
			])
			->select([
				'repair_order_mechanics.id',
				'repair_order_mechanics.name',
				'repair_order_mechanics.code',
				DB::raw('IF(repair_order_mechanics.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('repair_order_mechanics.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'repair_order_mechanics' => $repair_order_mechanics,
		]);
	}
}