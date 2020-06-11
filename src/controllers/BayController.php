<?php

namespace Abs\GigoPkg;

use Abs\StatusPkg\Status;
use App\Bay;
use App\Config;
use App\Http\Controllers\Controller;
use App\Outlet;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class BayController extends Controller {
	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getBayFilter() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];

		$this->data['outlet_list'] = collect(Outlet::select('id', 'code', 'name')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'code' => 'Select Outlet Code', 'name' => 'Name']);

		$this->data['area_type_list'] = collect(Config::select('id', 'name')->where('config_type_id',120)->get())->prepend(['id' => '', 'name' => 'Select Area Type']);

		return response()->json($this->data);
	}

	public function getBayList(Request $request) {
// dd($request->area_type_id);
		$bays = Bay::withTrashed()
			->select([
				'bays.short_name',
				'bays.id',
				'bays.name',
				// 'outlet',
				'outlets.code as outlet',
				'configs.name as bay_status',
				'area_type.name as area_type',

				DB::raw('IF(bays.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->leftJoin('outlets', 'outlets.id', 'bays.outlet_id')
			->leftJoin('configs', 'configs.id', 'bays.status_id')
			->join('configs as area_type', 'area_type.id', 'bays.area_type_id')

		// ->leftJoin('job_orders', 'job_orders.id', 'bays.job_order_id')

			->where(function ($query) use ($request) {
				if (!empty($request->short_name)) {
					$query->where('bays.short_name', 'LIKE', '%' . $request->short_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('bays.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->outlet)) {
					$query->where('bays.outlet_id', 'LIKE', '%' . $request->outlet . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->area_type)) {
					$query->where('bays.area_type_id', $request->area_type);
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('bays.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('bays.deleted_at');
				}
			})
		;

		return Datatables::of($bays)
			// ->addColumn('outlet', function ($bays) {
			// 	return $bays->code."/".$bays->name;
			// })
			->addColumn('status', function ($bays) {
				$status = $bays->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indigator ' . $status . '"></span>' . $bays->status;
			})
			->addColumn('action', function ($bays) {
				$img_edit = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img_edit_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-bay')) {
					$output .= '<a href="#!/gigo-pkg/bay/edit/' . $bays->id . '" id = "" title="Edit"><img src="' . $img_edit . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img_edit_active . '" onmouseout=this.src="' . $img_edit . '"></a>';
				}
				if (Entrust::can('delete-bay')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_bay" onclick="angular.element(this).scope().deleteBay(' . $bays->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>
					';
				}
				return $output;
			})
			->make(true);
	}

	public function getBayFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$bay = new Bay;
			$action = 'Add';
		} else {
			$bay = Bay::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['extras'] = [
			'outlet_list' => collect(Outlet::select('id', 'code', 'name')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'code' => 'Select Outlet Code', 'name' => 'Name']),
			'area_type_list' => collect(Config::select('id', 'name')->where('config_type_id',120)->get())->prepend(['id' => '', 'name' => 'Select Area Type']),
		];

		$this->data['bay'] = $bay;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveBay(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'short_name.required' => 'Short Name is Required',
				'short_name.unique' => 'Short Name is already taken',
				'short_name.min' => 'Short Name is Minimum 3 Charachers',
				'short_name.max' => 'Short Name is Maximum 32 Charachers',
				// 'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 128 Charachers',
				'outlet_id.required' => 'Outlet is Required',
				'area_type_id.required' => 'Area Type is Required',
			];
			$validator = Validator::make($request->all(), [
				'short_name' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:bays,short_name,' . $request->id . ',id,outlet_id,' . $request->outlet_id,
				],
				'name' => [
					// 'required:true',
					'min:3',
					'max:128',
					'nullable',
					'unique:bays,name,' . $request->id . ',id,outlet_id,' . $request->outlet_id,
				],
				'outlet_id' => 'required',
				'area_type_id' => 'required',
				// 'job_order_id' => [
				// 	'nullable',
				// 	'unique:job_orders,number,' . $request->id,
				// ],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$bay = new Bay;
				// $bay->fill($request->all());
				$bay->status_id = 8240; //Free
				$bay->created_by_id = Auth::user()->id;
				$bay->created_at = Carbon::now();
				$bay->updated_at = NULL;
			} else {
				$bay = Bay::withTrashed()->find($request->id);
				$bay->updated_by_id = Auth::user()->id;
				$bay->updated_at = Carbon::now();
			}
			$bay->fill($request->all());
			if ($request->status == 'Inactive') {
				$bay->deleted_at = Carbon::now();
				$bay->deleted_by_id = Auth::user()->id;
			} else {
				$bay->deleted_by_id = NULL;
				$bay->deleted_at = NULL;
			}
			$bay->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Bay Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Bay Updated Successfully',
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

	public function deleteBay(Request $request) {
		DB::beginTransaction();
		try {
			$bay = Bay::withTrashed()->where('id', $request->id)->forceDelete();
			if ($bay) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Bay Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
