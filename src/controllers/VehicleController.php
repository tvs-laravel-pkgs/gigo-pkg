<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use Abs\GigoPkg\Vehicle;
use Abs\GigoPkg\ModelType;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class VehicleController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getVehicleFilterData() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		$this->data['model_list'] = ModelType::select('id','model_name')->where('company_id',Auth::user()->company_id)->get();
		
		return response()->json($this->data);
	}

	public function getVehicleList(Request $request) {
		$vehicles = Vehicle::withTrashed()
			->select([
				'vehicles.id',
				'vehicles.engine_number',
				'vehicles.chassis_number',
				'models.model_name',
				'vehicles.registration_number',
				'vehicles.vin_number',
				'vehicles.sold_date',
				DB::raw('IF(vehicles.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->leftJoin('models', 'models.id', 'vehicles.model_id')
			->where('vehicles.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->engine_numbers)) {
					$query->where('vehicles.engine_number', 'LIKE', '%' . $request->engine_numbers . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->chassis_numbers)) {
					$query->where('vehicles.chassis_number', 'LIKE', '%' . $request->chassis_numbers . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->model_ids)) {
					$query->where('vehicles.model_id', 'LIKE', '%' . $request->model_ids. '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->registration_numbers)) {
					$query->where('vehicles.registration_number', 'LIKE', '%' . $request->registration_numbers . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->vin_numbers)) {
					$query->where('vehicles.vin_number', 'LIKE', '%' . $request->vin_numbers . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('vehicles.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('vehicles.deleted_at');
				}
			})
		;

		return Datatables::of($vehicles)
			->addColumn('status', function ($vehicles) {
				$status = $vehicles->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indigator ' . $status . '"></span>' . $vehicles->status;
			})
			->addColumn('action', function ($vehicle) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-vehicle')) {
					$output .= '<a href="#!/gigo-pkg/vehicle/edit/' . $vehicle->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-vehicle')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#vehicle-delete-modal" onclick="angular.element(this).scope().deleteVehicle(' . $vehicle->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getVehicleFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$vehicle = new Vehicle;
			$action = 'Add';
		} else {
			$vehicle = Vehicle::withTrashed()->find($id);
			$action = 'Edit';
			$this->data['sold_date'] = date('d-m-Y', strtotime($vehicle->sold_date));
		}
		$this->data['model_list'] = ModelType::select('id','model_name')->where('company_id',Auth::user()->company_id)->get();
		$this->data['vehicle'] = $vehicle;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveVehicle(Request $request) {
		try {
			$error_messages = [
				'engine_number.required' => 'Engine Number is Required',
				'engine_number.unique' => 'Engine Number is already taken',
				'engine_number.min' => 'Engine Number is Minimum 10 Charachers',
				'engine_number.max' => 'Engine Number is Maximum 32 Charachers',
				'chassis_number.required' => 'Chassis Number is Required',
				'chassis_number.unique' => 'Chassis Number is already taken',
				'chassis_number.min' => 'Chassis Number is Minimum 10 Charachers',
				'chassis_number.max' => 'Chassis Number is Maximum 32 Charachers',
				'model_id.required' => 'Model is Required',
				'registration_number.required' => 'Registration Number is Required',
				'registration_number.unique' => 'Registration Number is already taken',
				'registration_number.min' => 'Registration Number is Minimum 10 Charachers',
				'registration_number.max' => 'Registration Number is Maximum 32 Charachers',
				'registration_number.required' => 'Registration Number is Required',
				'vin_number.unique' => 'Vin Number is already taken',
				'vin_number.min' => 'Vin Number is Minimum 10 Charachers',
				'vin_number.max' => 'Vin Number is Maximum 32 Charachers',
				'vin_number.required' => 'Vin Number is Required',
				
				'sold_date.required' => 'Name is Required',
				
			];
			$validator = Validator::make($request->all(), [
				'engine_number' => [
					'required:true',
					'min:10',
					'max:32',
					'unique:vehicles,engine_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'chassis_number' => [
					'required:true',
					'min:10',
					'max:32',
					'unique:vehicles,chassis_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'registration_number' => [
					'required:true',
					'min:10',
					'max:32',
					'unique:vehicles,registration_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'vin_number' => [
					'required:true',
					'min:10',
					'max:32',
					'unique:vehicles,vin_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'model_id' => 'required',
				'sold_date' => 'required',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$vehicle = new Vehicle;
				$vehicle->created_by_id = Auth::user()->id;
				$vehicle->created_at = Carbon::now();
				$vehicle->updated_at = NULL;
			} else {
				$vehicle = Vehicle::withTrashed()->find($request->id);
				$vehicle->updated_by_id = Auth::user()->id;
				$vehicle->updated_at = Carbon::now();
			}
			$vehicle->fill($request->all());
			$vehicle->company_id = Auth::user()->company_id;
			$vehicle->sold_date = date('Y-m-d', strtotime($request->sold_date));
			if ($request->register_val == 'Yes') {
				$vehicle->is_registered = 1;
			} else {
				$vehicle->is_registered = 0;
			}
			if ($request->status == 'Inactive') {
				$vehicle->deleted_at = Carbon::now();
				$vehicle->deleted_by_id = Auth::user()->id;
			} else {
				$vehicle->deleted_by_id = NULL;
				$vehicle->deleted_at = NULL;
			}

			$vehicle->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Updated Successfully',
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

	public function deleteVehicle(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$vehicle = Vehicle::withTrashed()->where('id', $request->id)->forceDelete();
			if ($vehicle) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Vehicle Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getVehicles(Request $request) {
		$vehicles = Vehicle::withTrashed()
			->with([
				'vehicles',
				'vehicles.user',
			])
			->select([
				'vehicles.id',
				'vehicles.name',
				'vehicles.code',
				DB::raw('IF(vehicles.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicles.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'vehicles' => $vehicles,
		]);
	}
}