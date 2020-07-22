<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\RepairOrder;
use Abs\GigoPkg\ServiceType;
use Abs\PartPkg\Part;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class ServiceTypeController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getServiceTypeFilterData() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getServiceTypeList(Request $request) {
		$service_types = ServiceType::withTrashed()

			->select([
				'service_types.id',
				'service_types.name',
				'service_types.code',
				DB::raw('IF(service_types.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('service_types.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->short_name)) {
					$query->where('service_types.code', 'LIKE', '%' . $request->short_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('service_types.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('service_types.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('service_types.deleted_at');
				}
			})
		;

		return Datatables::of($service_types)
			->addColumn('status', function ($service_types) {
				$status = $service_types->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indigator ' . $status . '"></span>' . $service_types->status;
			})
			->addColumn('action', function ($service_type) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-service-type')) {
					$output .= '<a href="#!/gigo-pkg/service-type/edit/' . $service_type->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-service-type')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#service_type-delete-modal" onclick="angular.element(this).scope().deleteServiceType(' . $service_type->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getServiceTypeFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$service_type = new ServiceType;
			$service_type->service_type_labours = [];
			$service_type->service_type_parts = [];
			$action = 'Add';
		} else {
			$service_type = ServiceType::withTrashed()
				->with([
					// 'serviceTypeLabours',
					// 'serviceTypeLabours.repairOrderType',
				])->find($id);
			// $service_type->service_type_labours = $labours = $service_type->serviceTypeLabours()->select('id')->get();
			// if ($service_type->service_type_labours) {
			// 	foreach ($labours as $key => $labour) {
			// 		$service_type->service_type_labours[$key]->name = $switch_value = RepairOrder::join('repair_order_types', 'repair_order_types.id', 'repair_orders.type_id')->join('repair_order_service_type', 'repair_order_service_type.repair_order_id', 'repair_orders.id')->where('repair_order_service_type.service_type_id', $id)->where('repair_orders.id', $labour->id)->select('repair_orders.id', 'repair_orders.code', 'repair_orders.hours', 'repair_orders.amount', 'repair_order_types.name as repair_order_type',
			// 			DB::raw('IF(repair_order_service_type.is_free_service =1,"Yes","No") as switch_value'))
			// 			->first();
			// 		$service_type->service_type_labours[$key]->switch_value = $switch_value->switch_value;
			// 	}
			// }

			// $service_type->service_type_parts = $parts = $service_type->serviceTypeParts()->select('id')->get();
			// if ($service_type->service_type_parts) {
			// 	foreach ($parts as $key => $part) {
			// 		$service_type->service_type_parts[$key]->name = $switch_value = Part::join('tax_codes', 'tax_codes.id', 'parts.tax_code_id')->join('part_service_type', 'part_service_type.part_id', 'parts.id')->where('parts.id', $part->id)->where('part_service_type.service_type_id', $id)->select('parts.id', 'parts.code', 'parts.name', 'parts.rate', 'tax_codes.code as tax_code_type', 'part_service_type.quantity', 'part_service_type.amount',
			// 			DB::raw('IF(part_service_type.is_free_service =1,"Yes","No") as switch_value')
			// 		)->first();
			// 		$service_type->service_type_parts[$key]->switch_value = $switch_value->switch_value;
			// 	}
			// }

			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['service_type'] = $service_type;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveServiceType(Request $request) {
		try {
			$error_messages = [
				'code.required' => 'Code is Required',
				'code.unique' => 'Code is already taken',
				'code.min' => 'Code is Minimum 3 Charachers',
				'code.max' => 'Code is Maximum 32 Charachers',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:service_types,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'nullable',
					'min:3',
					'max:191',
					'unique:service_types,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'labours.*.id' => [
					'required',
					'integer',
					'exists:repair_orders,id',
					'distinct',
				],
				'parts.*.id' => [
					'required',
					'integer',
					'exists:parts,id',
					'distinct',
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$service_type = new ServiceType;
				$service_type->created_by_id = Auth::user()->id;
				$service_type->created_at = Carbon::now();
				$service_type->updated_at = NULL;
			} else {
				$service_type = ServiceType::withTrashed()->find($request->id);
				$service_type->updated_by_id = Auth::user()->id;
				$service_type->updated_at = Carbon::now();
			}
			$service_type->fill($request->all());
			$service_type->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$service_type->deleted_at = Carbon::now();
				$service_type->deleted_by_id = Auth::user()->id;
			} else {
				$service_type->deleted_by_id = NULL;
				$service_type->deleted_at = NULL;
			}
			$service_type->save();

			// $service_type->serviceTypeLabours()->sync([]);
			// $service_type->serviceTypeParts()->sync([]);

			// if ($request->labours) {
			// 	$total_labours = array_column($request->labours, 'id');
			// 	$total_labours_unique = array_unique($total_labours);
			// 	if (count($total_labours) != count($total_labours_unique)) {
			// 		return response()->json(['success' => false, 'errors' => ['Labours already been taken']]);
			// 	}

			// 	foreach ($request->labours as $labour) {
			// 		$service_type->serviceTypeLabours()->attach($labour['id'], ['is_free_service' => $labour['status'] == 'Yes' ? '1' : '0']);
			// 	}
			// }

			// if ($request->parts) {
			// 	$total_parts = array_column($request->parts, 'id');
			// 	$total_parts_unique = array_unique($total_parts);
			// 	if (count($total_parts) != count($total_parts_unique)) {
			// 		return response()->json(['success' => false, 'errors' => ['Parts already been taken']]);
			// 	}

			// 	foreach ($request->parts as $parts) {
			// 		$service_type->serviceTypeParts()->attach($parts['id'], ['quantity' => $parts['qty'], 'amount' => $parts['amount'], 'is_free_service' => $parts['status'] == 'Yes' ? '1' : '0']);
			// 	}
			// }

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Service Type Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Service Type Updated Successfully',
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

	public function deleteServiceType(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$service_type = ServiceType::withTrashed()->where('id', $request->id)->forceDelete();
			if ($service_type) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Service Type Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getServiceTypes(Request $request) {
		$service_types = ServiceType::withTrashed()
			->with([
				'service-types',
				'service-types.user',
			])
			->select([
				'service_types.id',
				'service_types.name',
				'service_types.code',
				DB::raw('IF(service_types.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('service_types.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'service_types' => $service_types,
		]);
	}

	public function getLabourSearchList(Request $r) {
		$key = $r->key;
		$list = RepairOrder::join('repair_order_types', 'repair_order_types.id', 'repair_orders.type_id')->select(
			'repair_orders.id',
			'repair_orders.hours',
			'repair_orders.amount',
			'repair_orders.code',
			'repair_orders.name',
			'repair_order_types.name as repair_order_type'
		)
			->where(function ($q) use ($key) {
				$q->where('repair_orders.name', 'like', $key . '%')
					->orWhere('repair_orders.code', 'like', '%' . $key . '%')
					->orWhere('repair_orders.alt_code', 'like', '%' . $key . '%')
				;
			})
			->where('repair_orders.company_id', Auth::user()->company_id)
			->get();
		return response()->json($list);
	}

	public function getPartSearchList(Request $r) {
		$key = $r->key;
		$list = Part::join('tax_codes', 'tax_codes.id', 'parts.tax_code_id')->select(
			'parts.id',
			'parts.rate',
			'parts.code',
			'parts.name',
			'tax_codes.code as tax_code_type'
		)
			->where(function ($q) use ($key) {
				$q->where('parts.name', 'like', $key . '%')
					->orWhere('parts.code', 'like', '%' . $key . '%')
				;
			})
			->where('parts.company_id', Auth::user()->company_id)
			->get();
		return response()->json($list);
	}
}