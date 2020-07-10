<?php

namespace Abs\GigoPkg;
use App\Config;
use App\Http\Controllers\Controller;
use App\SplitOrderType;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class SplitOrderTypeController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getSplitOrderTypeFilter() {
		$params['config_type_id'] = 400; //PAID BY
		$params['add_default'] = true;
		$params['default_text'] = 'Select Paid By';
		$this->data['extras'] = [
			'paid_by' => Config::getDropDownList($params),
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getSplitOrderTypeList(Request $request) {
		$split_order_types = SplitOrderType::withTrashed()

			->select([
				'split_order_types.id',
				'split_order_types.code',
				'split_order_types.name',
				DB::raw('IF(configs.name IS NULL, "--",configs.name) as paid_by'),
				DB::raw('IF(split_order_types.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->leftJoin('configs', 'configs.id', 'split_order_types.paid_by_id')
			->where('split_order_types.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->code)) {
					$query->where('split_order_types.code', 'LIKE', '%' . $request->code . '%');
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('split_order_types.name', 'LIKE', '%' . $request->name . '%');
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->paid_by_id)) {
					$query->where('split_order_types.paid_by_id', $request->paid_by_id);
				}
			})

			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('split_order_types.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('split_order_types.deleted_at');
				}
			})
		;

		return Datatables::of($split_order_types)

			->addColumn('status', function ($split_order_type) {
				$status = $split_order_type->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $split_order_type->status;
			})

			->addColumn('action', function ($split_order_type) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$action = '';
				if (Entrust::can('edit-split-order-type')) {
					$action .= '<a href="#!/gigo-pkg/split-order-type/edit/' . $split_order_type->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';

				}
				if (Entrust::can('delete-split-order-type')) {
					$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_split_order_type" onclick="angular.element(this).scope().deleteSplitOrderType(' . $split_order_type->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';

				}
				return $action;
			})
			->make(true);
	}

	public function getSplitOrderTypeFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$split_order_type = new SplitOrderType;
			$action = 'Add';
		} else {
			$split_order_type = SplitOrderType::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['split_order_type'] = $split_order_type;
		$this->data['action'] = $action;

		$params['config_type_id'] = 400; //PAID BY
		$params['add_default'] = true;
		$params['default_text'] = 'Select Paid By';
		$this->data['extras'] = [
			'paid_by' => Config::getDropDownList($params),
		];

		return response()->json($this->data);
	}

	public function saveSplitOrderType(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Code is Required',
				'code.unique' => 'Code is already taken',
				'code.min' => 'Code is Minimum 3 Charachers',
				'code.max' => 'Code is Maximum 32 Charachers',
				// 'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:split_order_types,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'min:3',
					'max:191',
					'nullable',
					// 'unique:split_order_types,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'paid_by_id' => [
					'required:true',
					'exists:configs,id',
					'integer',
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
				$split_order_type = new SplitOrderType;
				$split_order_type->created_by_id = Auth::user()->id;
			} else {
				$split_order_type = SplitOrderType::withTrashed()->find($request->id);
				$split_order_type->updated_by_id = Auth::user()->id;
			}
			$split_order_type->company_id = Auth::user()->company_id;
			// if (!$request->id) {
			// 	$split_order_type = new SplitOrderType;
			// 	$split_order_type->company_id = Auth::user()->company_id;
			// } else {
			// 	$split_order_type = SplitOrderType::withTrashed()->find($request->id);
			// }
			$split_order_type->fill($request->all());
			if ($request->status == 'Inactive') {
				$split_order_type->deleted_at = Carbon::now();
				$split_order_type->deleted_by_id = Auth::user()->id;
			} else {
				$split_order_type->deleted_at = NULL;
				$split_order_type->deleted_by_id = NULL;
			}
			$split_order_type->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Split Order Type Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Split Order Type Updated Successfully',
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

	public function deleteSplitOrderType(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$split_order_type = SplitOrderType::withTrashed()->where('id', $request->id)->forceDelete();
			if ($split_order_type) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Split Order Type Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	// public function getSplitOrderTypes(Request $request) {
	// 	$split_order_types = SplitOrderType::withTrashed()
	// 		->with([
	// 			'split-order-types',
	// 			'split-order-types.user',
	// 		])
	// 		->select([
	// 			'split_order_types.id',
	// 			'split_order_types.name',
	// 			'split_order_types.code',
	// 			DB::raw('IF(split_order_types.deleted_at IS NULL, "Active","Inactive") as status'),
	// 		])
	// 		->where('split_order_types.company_id', Auth::user()->company_id)
	// 		->get();

	// 	return response()->json([
	// 		'success' => true,
	// 		'split_order_types' => $split_order_types,
	// 	]);
	// }
}