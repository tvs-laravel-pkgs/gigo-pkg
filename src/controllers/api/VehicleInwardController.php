<?php

namespace Abs\GigoPkg\Api;

use App\Address;
use App\Config;
use App\Country;
use App\Customer;
use App\CustomerDetails;
use App\CustomerVoice;
use App\GateLog;
use App\Http\Controllers\Controller;
use App\jobOrder;
use App\State;
use App\User;
use App\Vehicle;
use App\VehicleInspectionItemGroup;
use App\VehicleModel;
use App\VehicleOwner;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;

class VehicleInwardController extends Controller {
	public $successStatus = 200;

	public function getVehicleInwardList(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				'employee_id' => [
					'required',
					'exists:employees,id',
					'integer',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			$gate_log_ids = [];
			$gate_logs = GateLog::
				where('gate_logs.company_id', Auth::user()->company_id)
				->get();
			foreach ($gate_logs as $key => $gate_log) {
				if ($gate_log->status_id == 8120) {
					//Gate In Completed
					$gate_log_ids[] = $gate_log->id;
				} else {
// Others
					if ($gate_log->floor_adviser_id == $request->employee_id) {
						$gate_log_ids[] = $gate_log->id;
					}
				}
			}

			$vehicle_inward_list = GateLog::select('gate_logs.*')
				->with([
					'vehicleDetail',
					'vehicleDetail.vehicleOwner',
					'vehicleDetail.vehicleOwner.CustomerDetail',
				])
				->leftJoin('vehicles', 'gate_logs.vehicle_id', 'vehicles.id')
				->leftJoin('vehicle_owners', 'vehicles.id', 'vehicle_owners.vehicle_id')
				->leftJoin('customers', 'vehicle_owners.customer_id', 'customers.id')
				->whereIn('gate_logs.id', $gate_log_ids)
				->where(function ($query) use ($request) {
					if (isset($request->search_key)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('customers.name', 'LIKE', '%' . $request->search_key . '%');
					}
				})
				->get();

			return response()->json([
				'success' => true,
				'vehicle_inward_list' => $vehicle_inward_list,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}
	public function getVehicleFormData($id) {
		// dd($id);
		try {
			$gate_log_validate = GateLog::find($id);
			if (!$gate_log_validate) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}

			//UPDATE GATE LOG
			$gate_log = GateLog::where('id', $id)
				->update([
					'status_id' => 8121, //VEHICLE INWARD INPROGRESS
					'floor_adviser_id' => Auth::user()->entity_id,
					'updated_by_id' => Auth::user()->id,
				]);

			$gate_log_detail = GateLog::with(['vehicleDetail'])->find($id);

			$extras = [
				'registration_types' => [
					['id' => 0, 'name' => 'Unregistred'],
					['id' => 1, 'name' => 'Registred'],
				],
				'vehicle_models' => VehicleModel::getList(),
			];

			return response()->json([
				'success' => true,
				'gate_log_detail' => $gate_log_detail,
				'extras' => $extras,
			]);
			// return VehicleInward::saveVehicleGateInEntry($request);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveVehicle(Request $request) {
		// dd($request->all());

		try {
			//REMOVE WHITE SPACE BETWEEN REGISTRATION NUMBER
			$request->registration_number = str_replace(' ', '', $request->registration_number);

			//REGISTRATION NUMBER VALIDATION
			if ($request->registration_number) {
				$error = '';
				$first_two_string = substr($request->registration_number, 0, 2);
				$next_two_number = substr($request->registration_number, 2, 2);
				$last_two_number = substr($request->registration_number, -2);
				if (!preg_match('/^[A-Z]+$/', $first_two_string) && !preg_match('/^[0-9]+$/', $next_two_number) && !preg_match('/^[0-9]+$/', $last_two_number)) {
					$error = "Please enter valid registration number!";
				}
				if ($error) {
					return response()->json([
						'success' => false,
						'error' => $error,
					]);
				}
			}

			$validator = Validator::make($request->all(), [
				'is_registered' => [
					'required',
					'integer',
				],
				'registration_number' => [
					'required',
					'min:6',
					'string',
					'max:10',
				],
				'model_id' => [
					'required',
					'exists:models,id',
					'integer',
				],
				'engine_number' => [
					'required',
					'min:7',
					'max:64',
					'string',
					'unique:vehicles,engine_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'chassis_number' => [
					'required',
					'min:10',
					'max:64',
					'string',
					'unique:vehicles,chassis_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'vin_number' => [
					'required',
					'min:17',
					'max:32',
					'string',
					'unique:vehicles,vin_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();
			//VEHICLE GATE ENTRY DETAILS
			// UNREGISTRED VEHICLE DIFFERENT FLOW WAITING FOR REQUIREMENT
			if ($request->is_registered != 1) {
				return response()->json([
					'success' => false,
					'error' => 'Unregistred Vehile Not allow!!',
				]);
			}

			//ONLY FOR REGISTRED VEHICLE
			$vehicle = Vehicle::firstOrNew([
				'company_id' => Auth::user()->company_id,
				'registration_number' => $request->registration_number,
			]);
			$vehicle->fill($request->all());
			$vehicle->status_id = 8141; //Customer Not Mapped
			$vehicle->company_id = Auth::user()->company_id;
			$vehicle->updated_by_id = Auth::user()->id;
			$vehicle->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Vehicle detail updated Successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function getCustomerFormData($id) {
		try {

			$gate_log_details = GateLog::with([
				'vehicleDetail',
				'vehicleDetail.vehicleOwner',
				'vehicleDetail.vehicleOwner.CustomerDetail',
				'vehicleDetail.vehicleOwner.CustomerDetail.primaryAddress',
				'vehicleDetail.vehicleOwner.CustomerDetail.primaryAddress.country',
				'vehicleDetail.vehicleOwner.CustomerDetail.primaryAddress.state',
				'vehicleDetail.vehicleOwner.CustomerDetail.primaryAddress.city',
			])->find($id);

			if (!$gate_log_details) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}

			$extras = [
				'country_list' => Country::getList(),
				'ownership_list' => Config::getConfigTypeList(39, 'id', '', true, 'Select Ownership'),
			];

			return response()->json([
				'success' => true,
				'gate_log_details' => $gate_log_details,
				'extras' => $extras,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function getState($country_id) {
		$this->data = Country::getState($country_id);
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function getcity($state_id) {
		$this->data = State::getCity($state_id);
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function saveCustomer(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'name' => [
					'required',
					'min:3',
					'string',
					'max:255',
				],
				'mobile_no' => [
					'required',
					'min:10',
					'max:10',
				],
				'email' => [
					'nullable',
					'max:255',
					'string',
				],
				'address_line1' => [
					'required',
					'min:3',
					'max:255',
					'string',
				],
				'address_line2' => [
					'nullable',
					'max:255',
					'string',
				],
				'country_id' => [
					'required',
					'exists:countries,id',
					'integer',
				],
				'state_id' => [
					'required',
					'exists:states,id',
					'integer',
				],
				'city_id' => [
					'required',
					'exists:cities,id',
					'integer',
				],
				'pincode' => [
					'required',
					'min:6',
					'max:6',
				],
				'gst_number' => [
					'nullable',
					'min:15',
					'max:15',
				],
				'pan_number' => [
					'nullable',
					'min:10',
					'max:10',
				],
				'ownership_id' => [
					'required',
					'exists:configs,id',
					'integer',
				],

			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();

			$gate_log = GateLog::with([
				'vehicleDetail',
				'vehicleDetail.vehicleOwner',
			])
				->find($request->gate_log_id);
			// dd($gate_log);
			if (!$gate_log->vehicleDetail->VehicleOwner) {
				$customer = new Customer;
				$customer_details = new CustomerDetails;
				$address = new Address;
				$vehicle_owner = new VehicleOwner;
				$customer->created_by_id = Auth::user()->id;
			} else {
				$customer = Customer::find($gate_log->vehicleDetail->VehicleOwner->customer_id);
				$vehicle_owner = VehicleOwner::find($gate_log->vehicleDetail->VehicleOwner->vehicle_id);
				$customer->updated_by_id = Auth::user()->id;
				$address = Address::where('address_of_id', 24)->where('entity_id', $gate_log->vehicleDetail->VehicleOwner->customer_id)->first();
			}
			$customer->code = rand(1, 10000);
			$customer->fill($request->all());
			$customer->company_id = Auth::user()->company_id;
			$customer->gst_number = $request->gst_number;
			$customer->save();
			$customer->code = 'CUS' . $customer->id;
			$customer->save();

			//SAVE VEHICLE OWNER
			$vehicle_owner->vehicle_id = $gate_log->vehicleDetail->id;
			$vehicle_owner->customer_id = $customer->id;
			$vehicle_owner->from_date = Carbon::now();
			$vehicle_owner->ownership_id = $request->ownership_id;
			$vehicle_owner->save();

			if (!$address) {
				$address = new Address;
			}
			$address->fill($request->all());
			$address->company_id = Auth::user()->company_id;
			$address->address_of_id = 24; //CUSTOMER
			$address->entity_id = $customer->id;
			$address->address_type_id = 40; //PRIMART ADDRESS
			$address->name = 'Primary Address';
			$address->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Vehicle Mapped with customer Successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}
	//PENDING REQUIREMENT PENDING START
	public function getVehicleInspectiongeFormData($id) {
		try {
			$gate_log_validate = GateLog::find($id);
			if (!$gate_log_validate) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}

			$vehicle_inspection_group = VehicleInspectionItemGroup::with([
				'VehicleInspectionItems',
			])
				->where('company_id', Auth::user()->company_id)
				->get();

			return response()->json([
				'success' => true,
				'vehicle_inspection_group' => $vehicle_inspection_group,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}
	//PENDING REQUIREMENT PENDING END

	public function getVocFormData($id) {
		try {
			$gate_log_detail = GateLog::with([
				'jobOrder',
				'jobOrder.customerVoice',
			])
				->find($id);

			if (!$gate_log_detail) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}

			$VOC_list = CustomerVoice::where('company_id', Auth::user()->company_id)
				->get();

			return response()->json([
				'success' => true,
				'VOC_list' => $VOC_list,
				'gate_log_detail' => $gate_log_detail,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveVoc(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'exists:job_orders,id',
					'integer',
				],
				'customer_voice_id.*' => [
					'integer',
					'exists:customer_voices,id',
					'distinct',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$job_order = jobOrder::find($request->job_order_id);

			$job_order->customerVoice()->sync([]);
			$job_order->customerVoice()->sync($request->customer_voice_id);

			return response()->json([
				'success' => true,
				'message' => 'VOC Added Successfully',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function getRoadTestObservationFormData($id) {
		try {
			$gate_log_detail = GateLog::with([
				'jobOrder',
			])
				->find($id);

			if (!$gate_log_detail) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}
			$extras = [
				'road_test_by' => Config::getConfigTypeList(36, 'name', '', false, ''),
				'employee_list' => User::getList(),
			];

			return response()->json([
				'success' => true,
				'gate_log_detail' => $gate_log_detail,
				'extras' => $extras,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveRoadTestObservation(Request $request) {
		// dd($request->all());
		try {
			if ($request->road_test_done_by_id == 8101) {
				// EMPLOYEE
				$validator_road_test = Validator::make($request->all(), [
					'road_test_performed_by_id' => [
						'required',
						'exists:users,id',
						'integer',
					],
				]);
				if ($validator_road_test->fails()) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => $validator_road_test->errors()->all(),
					]);
				}
			}
			$validator = Validator::make($request->all(), [
				'is_road_test_required' => [
					'required',
					'integer',
					'max:1',
				],
				'road_test_done_by_id' => [
					'required',
					'exists:configs,id',
					'integer',
				],
				'road_test_report' => [
					'required',
					'string',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$job_order = jobOrder::find($request->job_order_id);
			$job_order->is_road_test_required = $request->is_road_test_required;
			$job_order->road_test_done_by_id = $request->road_test_done_by_id;
			if ($request->road_test_done_by_id == 8101) {
				// EMPLOYEE
				$job_order->road_test_performed_by_id = $request->road_test_performed_by_id;
			}
			$job_order->road_test_report = $request->road_test_report;
			$job_order->save();

			return response()->json([
				'success' => true,
				'message' => 'Road Test Observation Added Successfully',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

}
