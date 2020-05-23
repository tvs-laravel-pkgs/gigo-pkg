<?php

namespace Abs\GigoPkg\Api;

use App\GateLog;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use DB;
use Illuminate\Http\Request;
use Validator;

class CustomerController extends Controller {
	public $successStatus = 200;

	//CUSTOMER GET FORM DATA
	public function getCustomerFormData($id) {
		try {

			$gate_log_details = GateLog::with([
				'vehicleDetail',
				'vehicleDetail.vehicleCurrentOwner',
				'vehicleDetail.vehicleCurrentOwner.CustomerDetail',
				'vehicleDetail.vehicleCurrentOwner.CustomerDetail.primaryAddress',
				'vehicleDetail.vehicleCurrentOwner.CustomerDetail.primaryAddress.country',
				'vehicleDetail.vehicleCurrentOwner.CustomerDetail.primaryAddress.state',
				'vehicleDetail.vehicleCurrentOwner.CustomerDetail.primaryAddress.city',
			])->find($id);

			if (!$gate_log_details) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}

			$extras = [
				'country_list' => Country::getList(),
				'ownership_list' => Config::getConfigTypeList(39, 'id', '', true, 'Select Ownership'), //VEHICLE OWNERSHIP TYPES
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

	//GET STATE BASED COUNTRY
	public function getState($country_id) {
		$this->data = Country::getState($country_id);
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	//GET CITY BASED STATE
	public function getcity($state_id) {
		$this->data = State::getCity($state_id);
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	//CUSTOMER SAVE
	public function saveCustomer(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'gate_log_id' => [
					'required',
					'exists:gate_logs,id',
					'integer',
				],
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

			if (empty($gate_log)) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Log Not Found!',
				]);
			}

			//OWNERSHIP ALREADY EXIST OR NOT
			$vehicle_owners_exist = VehicleOwner::where([
				'vehicle_id' => $gate_log->vehicleDetail->id,
				'ownership_id' => $request->ownership_id,
			])
				->first();

			if ($vehicle_owners_exist) {
				return response()->json([
					'success' => false,
					'message' => 'Ownership Alreay Taken in this Vehicle!',
				]);
			}

			$customer = Customer::firstOrNew([
				'name' => $request->name,
				'mobile_no' => $request->mobile_no,
			]);
			if ($customer->exists) {
				//FIRST
				$customer->updated_at = Carbon::now();
				$customer->updated_by_id = Auth::user()->id;

				$address = Address::where('address_of_id', 24)->where('entity_id', $customer->id)->first();
				$vehicle_owner = VehicleOwner::where([
					'vehicle_id' => $gate_log->vehicleDetail->id,
					'customer_id' => $customer->id,
				])
					->first();
				// dd($vehicle_owner);
			} else {
				//NEW
				$customer->created_at = Carbon::now();
				$customer->created_by_id = Auth::user()->id;
				$vehicle_owner = new VehicleOwner;
				$address = new Address;
			}
			//issue : vijay : customer updated_at save missing, vehicle owner updated_at & updated_by_id save missing
			$customer->code = mt_rand(1, 1000);
			$customer->fill($request->all());
			$customer->company_id = Auth::user()->company_id;
			$customer->gst_number = $request->gst_number;
			$customer->save();
			$customer->code = 'CUS' . $customer->id;
			$customer->save();

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
}
