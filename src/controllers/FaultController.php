<?php

namespace Abs\GigoPkg;

use Abs\GigoPkg\Fault;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class FaultController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getFaultFilter() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getFaultList(Request $request) {
		$faults = Fault::withTrashed()
			->select([
				'faults.id',
				'faults.code',
				'faults.name',

				DB::raw('IF(faults.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('faults.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->code)) {
					$query->where('faults.code', 'LIKE', '%' . $request->code . '%');
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('faults.name', 'LIKE', '%' . $request->name . '%');
				}
			})

			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('faults.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('faults.deleted_at');
				}
			})
		;

		return Datatables::of($faults)

			->addColumn('status', function ($fault) {
				$status = $fault->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $fault->status;
			})

			->addColumn('action', function ($fault) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$action = '';
				if (Entrust::can('edit-fault')) {
					$action .= '<a href="#!/gigo-pkg/fault/edit/' . $fault->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';

				}
				if (Entrust::can('delete-fault')) {
					$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_fault" onclick="angular.element(this).scope().deleteFault(' . $fault->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';

				}
				return $action;
			})
			->make(true);
	}

	public function getFaultFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$fault = new Fault;
			$action = 'Add';
		} else {
			$fault = Fault::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['fault'] = $fault;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveFault(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Code is Required',
				'code.unique' => 'Code is already taken',
				'code.min' => 'Code is Minimum 2 Charachers',
				'code.max' => 'Code is Maximum 32 Charachers',
				'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'min:2',
					'max:32',
					'unique:faults,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:faults,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
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
				$fault = new Fault;
				$fault->created_by_id = Auth::user()->id;
			} else {
				$fault = Fault::withTrashed()->find($request->id);
				$fault->updated_by_id = Auth::user()->id;
			}
			$fault->company_id = Auth::user()->company_id;

			$fault->fill($request->all());
			if ($request->status == 'Inactive') {
				$fault->deleted_at = Carbon::now();
				$fault->deleted_by_id = Auth::user()->id;
			} else {
				$fault->deleted_at = NULL;
				$fault->deleted_by_id = NULL;
			}
			$fault->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Fault Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Fault Updated Successfully',
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

	public function deleteFault(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$fault = Fault::withTrashed()->where('id', $request->id)->forceDelete();
			if ($fault) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Fault Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}