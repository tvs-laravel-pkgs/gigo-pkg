<?php

namespace Abs\GigoPkg;
use App\Customer;
use App\GatePass;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class GatePassController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getGatePassList(Request $request) {
		$gate_passes = GatePass::withTrashed()

			->select([
				'gate_passes.id',
				'gate_passes.name',
				'gate_passes.code',

				DB::raw('IF(gate_passes.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('gate_passes.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('gate_passes.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('gate_passes.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('gate_passes.deleted_at');
				}
			})
		;

		return Datatables::of($gate_passes)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($gate_pass) {
				$status = $gate_pass->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $gate_pass->name;
			})
			->addColumn('action', function ($gate_pass) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-gate_pass')) {
					$output .= '<a href="#!/gigo-pkg/gate_pass/edit/' . $gate_pass->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-gate_pass')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#gate_pass-delete-modal" onclick="angular.element(this).scope().deleteGatePass(' . $gate_pass->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getGatePassFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$gate_pass = new GatePass;
			$action = 'Add';
		} else {
			$gate_pass = GatePass::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['gate_pass'] = $gate_pass;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveGatePass(Request $request) {
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
					'unique:gate_passes,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:gate_passes,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$gate_pass = new GatePass;
				$gate_pass->company_id = Auth::user()->company_id;
			} else {
				$gate_pass = GatePass::withTrashed()->find($request->id);
			}
			$gate_pass->fill($request->all());
			if ($request->status == 'Inactive') {
				$gate_pass->deleted_at = Carbon::now();
			} else {
				$gate_pass->deleted_at = NULL;
			}
			$gate_pass->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Gate Pass Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Gate Pass Updated Successfully',
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

	public function deleteGatePass(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$gate_pass = GatePass::withTrashed()->where('id', $request->id)->forceDelete();
			if ($gate_pass) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Gate Pass Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getGatePasss(Request $request) {
		$gate_passes = GatePass::withTrashed()
			->with([
				'gate-passes',
				'gate-passes.user',
			])
			->select([
				'gate_passes.id',
				'gate_passes.name',
				'gate_passes.code',
				DB::raw('IF(gate_passes.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('gate_passes.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'gate_passes' => $gate_passes,
		]);
	}

	//Customer Search
	public function getCustomerSearchList(Request $request) {
		return Customer::searchCustomer($request);
	}

	//Customer Search
	public function getJobCardSearchList(Request $request) {
		return JobCard::searchJobCard($request);
	}
}