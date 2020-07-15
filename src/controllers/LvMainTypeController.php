<?php

namespace Abs\GigoPkg;

use Abs\GigoPkg\LvMainType;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class LvMainTypeController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getLvMainTypeFilter() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getLvMainTypeList(Request $request) {
		$lv_main_types = LvMainType::withTrashed()
			->select([
				'lv_main_types.id',
				'lv_main_types.name',

				DB::raw('IF(lv_main_types.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('lv_main_types.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->code)) {
					$query->where('lv_main_types.code', 'LIKE', '%' . $request->code . '%');
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('lv_main_types.name', 'LIKE', '%' . $request->name . '%');
				}
			})

			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('lv_main_types.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('lv_main_types.deleted_at');
				}
			})
		;

		return Datatables::of($lv_main_types)

			->addColumn('status', function ($lv_main_type) {
				$status = $lv_main_type->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $lv_main_type->status;
			})

			->addColumn('action', function ($lv_main_type) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$action = '';
				if (Entrust::can('edit-lv-main-type')) {
					$action .= '<a href="#!/gigo-pkg/lv-main-type/edit/' . $lv_main_type->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';

				}
				if (Entrust::can('delete-lv-main-type')) {
					$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_lv_main_type" onclick="angular.element(this).scope().deleteLvMainType(' . $lv_main_type->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';

				}
				return $action;
			})
			->make(true);
	}

	public function getLvMainTypeFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$lv_main_type = new LvMainType;
			$action = 'Add';
		} else {
			$lv_main_type = LvMainType::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['lv_main_type'] = $lv_main_type;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveLvMainType(Request $request) {
		try {
			$error_messages = [
				'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:lv_main_types,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
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
				$lv_main_type = new LvMainType;
				$lv_main_type->created_by_id = Auth::user()->id;
			} else {
				$lv_main_type = LvMainType::withTrashed()->find($request->id);
				$lv_main_type->updated_by_id = Auth::user()->id;
			}
			$lv_main_type->company_id = Auth::user()->company_id;

			$lv_main_type->fill($request->all());
			if ($request->status == 'Inactive') {
				$lv_main_type->deleted_at = Carbon::now();
				$lv_main_type->deleted_by_id = Auth::user()->id;
			} else {
				$lv_main_type->deleted_at = NULL;
				$lv_main_type->deleted_by_id = NULL;
			}
			$lv_main_type->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'LV Main Type Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'LV Main Type Updated Successfully',
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

	public function deleteLvMainType(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$lv_main_type = LvMainType::withTrashed()->where('id', $request->id)->forceDelete();
			if ($lv_main_type) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'LV Main Type Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}