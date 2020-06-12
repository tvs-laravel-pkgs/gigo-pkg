<?php

namespace Abs\GigoPkg;

use App\Http\Controllers\Controller;
use Abs\GigoPkg\PartSupplier;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class PartSupplierController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getPartSupplierFilterData() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getPartSupplierList(Request $request) {
		$part_suppliers = PartSupplier::withTrashed()
			->select([
				'part_suppliers.id',
				'part_suppliers.code',
				'part_suppliers.name',

				DB::raw('IF(part_suppliers.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('part_suppliers.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->code)) {
					$query->where('part_suppliers.code', 'LIKE', '%' . $request->code . '%');
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('part_suppliers.name', 'LIKE', '%' . $request->name . '%');
				}
			})

			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('part_suppliers.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('part_suppliers.deleted_at');
				}
			})
		;

		return Datatables::of($part_suppliers)

			->addColumn('status', function ($part_suppliers) {
				$status = $part_suppliers->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $part_suppliers->status;
			})

			->addColumn('action', function ($part_suppliers) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$action = '';
				if (Entrust::can('edit-part-supplier')) {
					$action .= '<a href="#!/gigo-pkg/part-supplier/edit/' . $part_suppliers->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';

				}
				if (Entrust::can('delete-part-supplier')) {
					$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_part_supplier" onclick="angular.element(this).scope().deletePartSupplier(' . $part_suppliers->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';

				}
				return $action;
			})
			->make(true);
	}

	public function getPartSupplierFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$part_suppliers = new PartSupplier;
			$action = 'Add';
		} else {
			$part_suppliers = PartSupplier::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['part_suppliers'] = $part_suppliers;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function savePartSupplier(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Code is Required',
				'code.unique' => 'Code is already taken',
				'code.min' => 'Code is Minimum 3 Charachers',
				'code.max' => 'Code is Maximum 32 Charachers',
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
					'unique:part_suppliers,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:part_suppliers,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
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
				$part_suppliers = new PartSupplier;
				$part_suppliers->created_by_id = Auth::user()->id;
			} else {
				$part_suppliers = PartSupplier::withTrashed()->find($request->id);
				$part_suppliers->updated_by_id = Auth::user()->id;
				$part_suppliers->updated_at = Carbon::now();
			}
			$part_suppliers->company_id = Auth::user()->company_id;
			
			$part_suppliers->fill($request->all());
			if ($request->status == 'Inactive') {
				$part_suppliers->deleted_at = Carbon::now();
				$part_suppliers->deleted_by_id = Auth::user()->id;
			} else {
				$part_suppliers->deleted_at = NULL;
				$part_suppliers->deleted_by_id = NULL;
			}
			$part_suppliers->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Part Supplier Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Part Supplier Updated Successfully',
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

	public function deletePartSupplier(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$part_suppliers = PartSupplier::withTrashed()->where('id', $request->id)->forceDelete();
			if ($part_suppliers) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Vehicle Primary Application Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
