<?php

namespace Abs\GigoPkg;


use App\Http\Controllers\Controller;

use App\Address;
use App\Config;
use App\Country;
use App\Customer;
use App\JobOrder;
use App\Otp;
use App\State;
use App\VehicleModel;
use App\Vehicle;
use Auth;
use DB;
use Entrust;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Storage;
use Validator;

class GigoSupportController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getVehicleInwardFilter() {
		$params = [
			'config_type_id' => 49,
			'add_default' => true,
			'default_text' => "Select Status",
		];
		$this->data['extras'] = [
			'registration_type_list' => [
				['id' => '', 'name' => 'Select Registration Type'],
				['id' => '1', 'name' => 'Registered Vehicle'],
				['id' => '0', 'name' => 'Un-Registered Vehicle'],
			],
			'status_list' => Config::getDropDownList($params),
		];
		return response()->json($this->data);
	}

	public function getGateInList(Request $request)
    {
        if ($request->date_range) {
			$date_range = explode(' to ', $request->date_range);
			$start_date = date('Y-m-d', strtotime($date_range[0]));
			$start_date = $start_date . ' 00:00:00';

			$end_date = date('Y-m-d', strtotime($date_range[1]));
			$end_date = $end_date . ' 23:59:59';
		} else {
			$start_date = date('Y-m-01 00:00:00');
			$end_date = date('Y-m-t 23:59:59');
		}
		$vehicle_inwards = JobOrder::join('gate_logs', 'gate_logs.job_order_id', 'job_orders.id')
			->leftJoin('vehicles', 'job_orders.vehicle_id', 'vehicles.id')
			->leftJoin('vehicle_owners', function ($join) {
				$join->on('vehicle_owners.vehicle_id', 'job_orders.vehicle_id')
					->whereRaw('vehicle_owners.from_date = (select MAX(vehicle_owners1.from_date) from vehicle_owners as vehicle_owners1 where vehicle_owners1.vehicle_id = job_orders.vehicle_id)');
			})
			->leftJoin('customers', 'customers.id', 'vehicle_owners.customer_id')
			->leftJoin('models', 'models.id', 'vehicles.model_id')
			->leftJoin('amc_members', 'amc_members.vehicle_id', 'vehicles.id')
			->leftJoin('amc_policies', 'amc_policies.id', 'amc_members.policy_id')
			->join('configs', 'configs.id', 'job_orders.status_id')
			->join('outlets', 'outlets.id', 'job_orders.outlet_id')
			->select(
				'job_orders.id',
				DB::raw('IF(vehicles.is_registered = 1,"Registered Vehicle","Un-Registered Vehicle") as registration_type'),
				'vehicles.registration_number',
				DB::raw('COALESCE(models.model_number, "-") as model_number'),
				'gate_logs.number',
				'job_orders.status_id',
				DB::raw('DATE_FORMAT(gate_logs.gate_in_date,"%d/%m/%Y, %h:%i %p") as date'),
				'job_orders.driver_name',
				'job_orders.driver_mobile_number as driver_mobile_number',
				'job_orders.is_customer_agreed',
				DB::raw('COALESCE(GROUP_CONCAT(amc_policies.name), "-") as amc_policies'),
				'configs.name as status',
				'outlets.code as outlet_code',
				DB::raw('COALESCE(customers.name, "-") as customer_name')
			)
			->where(function ($query) use ($start_date, $end_date) {
				$query->whereDate('gate_logs.gate_in_date', '>=', $start_date)
					->whereDate('gate_logs.gate_in_date', '<=', $end_date);
			})
			->where(function ($query) use ($request) {
				if (!empty($request->reg_no)) {
					$query->where('vehicles.registration_number', 'LIKE', '%' . $request->reg_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->membership)) {
					$query->where('amc_policies.name', 'LIKE', '%' . $request->membership . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->gate_in_no)) {
					$query->where('gate_logs.number', 'LIKE', '%' . $request->gate_in_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->registration_type == '1' || $request->registration_type == '0') {
					$query->where('vehicles.is_registered', $request->registration_type);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->customer_id)) {
					$query->where('vehicle_owners.customer_id', $request->customer_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->model_id)) {
					$query->where('vehicles.model_id', $request->model_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->status_id)) {
					$query->where('job_orders.status_id', $request->status_id);
				}
			})
			->where('job_orders.company_id', Auth::user()->company_id)
		;

		if (!Entrust::can('gigo-support-all-outlet')) {
			if (Entrust::can('gigo-support-mapped-outlet')) {
				$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
				array_push($outlet_ids, Auth::user()->employee->outlet_id);
				$vehicle_inwards->whereIn('job_orders.outlet_id', $outlet_ids);
			} else{
				$vehicle_inwards->where('job_orders.outlet_id', Auth::user()->employee->outlet_id);
			} 
		}

		$vehicle_inwards->groupBy('job_orders.id');
		$vehicle_inwards->orderBy('job_orders.created_at', 'DESC');

		return Datatables::of($vehicle_inwards)
			->rawColumns(['status', 'action'])
			->filterColumn('registration_type', function ($query, $keyword) {
				$sql = 'IF(vehicles.is_registered = 1,"Registered Vehicle","Un-Registered Vehicle")  like ?';
				$query->whereRaw($sql, ["%{$keyword}%"]);
			})
			->editColumn('status', function ($vehicle_inward) {
				$status = $vehicle_inward->status_id == '8460' || $vehicle_inward->status_id == '8469' || $vehicle_inward->status_id == '8471' || $vehicle_inward->status_id == '8472' ? 'blue' : 'green';
				return '<span class="text-' . $status . '">' . $vehicle_inward->status . '</span>';
			})
			->addColumn('action', function ($vehicle_inward) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$output = '';
				$output .= '<a href="#!/gigo-support/view/' . $vehicle_inward->id . '" id = "" title="View"><img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				return $output;
			})
			->make(true);
    }

	//Vehicle Model Search
    public function getVehicleModelSearchList(Request $request)
    {
        return VehicleModel::searchVehicleModel($request);
    }
	//Customer Search
	public function getCustomerSearchList(Request $request) {
		return Customer::searchCustomer($request);
	}

    public function view(Request $request){
        try {
            $job_order = JobOrder::with([
                'vehicle',
                'vehicle.model',
                'vehicle.status',
                'vehicle.currentOwner.customer',
                'vehicle.currentOwner.customer.address',
                'vehicle.currentOwner.customer.address.country',
                'vehicle.currentOwner.customer.address.state',
                'vehicle.currentOwner.customer.address.city',
                'vehicle.currentOwner.ownershipType',
                'vehicle.lastJobOrder',
                'vehicle.lastJobOrder.jobCard',
                'vehicleInventoryItem',
                'vehicleInspectionItems',
                'type',
                'outlet',
                'customerVoices',
                'quoteType',
                'serviceType',
                'kmReadingType',
                'status',
                'gateLog',
                'gateLog.createdBy',
                'roadTestDoneBy',
                'roadTestPreferedBy',
                'expertDiagnosisReportBy',
                'estimationType',
                'driverLicenseAttachment',
                'insuranceAttachment',
                'rcBookAttachment',
                'warrentyPolicyAttachment',
                'EWPAttachment',
                'AMCAttachment',
                'gateLog.driverAttachment',
                'gateLog.kmAttachment',
                'gateLog.vehicleAttachment',
                'gateLog.chassisAttachment',
                'customerApprovalAttachment',
                'customerESign',
                'VOCAttachment',
                'CREUser',
                'tradePlateNumber',
                'frontSideAttachment',
                'backSideAttachment',
                'leftSideAttachment',
                'rightSideAttachment',
                'otherVehicleAttachment',
                'amcMember',
                'amcMember.amcPolicy',
                'GateInTradePlateNumber',
                'GateInTradePlateNumber.outlet',
                'gateInDriverSign',
                'gateInSecuritySign',
                'gateOutDriverSign',
                'gateOutSecuritySign',
                'serviceAdviser',
            ])
                ->select([
                    'job_orders.*',
                    DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
                    DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
                ])
                ->where('company_id', Auth::user()->company_id)
                ->find($request->id);
				$ownership_type_list =Config::getDropDownList(['config_type_id' => 39, 'default_text' => 'Select Ownership', 'orderBy' => 'id']);
				$state_list = collect(State::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select State']);
            
				if (!$job_order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Job Order Not Found!',
                    ],
                ]);
            }
			
			//TO GET OTP LIST
			$otps = Otp::select(
				'otps.*',
				'outlets.code as outlet_code',
				'configs.name as otp_type',
				'job_orders.number as job_order_number',
				'users.name as generatd_by'
				)
			->leftJoin('outlets','otps.outlet_id','outlets.id')
			->leftJoin('configs','otps.entity_type_id','configs.id')
			->leftJoin('job_orders','otps.entity_id','job_orders.id')
			->leftJoin('users','otps.created_by_id','users.id')
			->where('otps.entity_id',$request->id)
			->orderBy('otps.created_at', 'DESC')
			->get();

            //Job card details need to get future
            return response()->json([
                'success' => true,
                'job_order' => $job_order,
				'ownership_type_list' => $ownership_type_list,
				'country_list' => Country::getDropDownList(),
				'state_list'=> $state_list,
				'otps'=> $otps,
				'trade_plate_number_list' => TradePlateNumber::get(),
                'attachement_path' => url('storage/app/public/gigo/gate_in/attachments/'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Server Error',
                'errors' => [
                    'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
                ],
            ]);
        }
    }

	public function save(Request $request){
		//  dd($request->all());
	
		if($request->type_id == 1){//Vehicle Details
			try {
				//REMOVE WHITE SPACE BETWEEN REGISTRATION NUMBER
				$request->registration_number = str_replace(' ', '', $request->registration_number);
	
				//REGISTRATION NUMBER VALIDATION
				$error = '';
				if ($request->registration_number) {
					$registration_no_count = strlen($request->registration_number);
					if ($registration_no_count < 10) {
						return response()->json([
							'success' => false,
							'error' => 'Validation Error',
							'errors' => [
								'The registration number must be at least 10 characters.',
							],
						]);
					} else {
	
						$registration_number = explode('-', $request->registration_number);
	
						if (count($registration_number) > 2) {
							$valid_reg_number = 1;
							if (!preg_match('/^[A-Z]+$/', $registration_number[0]) || !preg_match('/^[0-9]+$/', $registration_number[1])) {
								$valid_reg_number = 0;
							}
	
							if (count($registration_number) > 3) {
								if (!preg_match('/^[A-Z]+$/', $registration_number[2]) || strlen($registration_number[3]) != 4 || !preg_match('/^[0-9]+$/', $registration_number[3])) {
									$valid_reg_number = 0;
								}
							} else {
								if (!preg_match('/^[0-9]+$/', $registration_number[2]) || strlen($registration_number[2]) != 4) {
									$valid_reg_number = 0;
								}
							}
						} else {
							$valid_reg_number = 0;
						}
	
						if ($valid_reg_number == 0) {
							return response()->json([
								'success' => false,
								'error' => 'Validation Error',
								'errors' => [
									"Please enter valid registration number!",
								],
							]);
						}
					}
				}
				$request->registration_number = str_replace('-', '', $request->registration_number);
	
				$request['registration_number'] = $request->registration_number ? str_replace('-', '', $request->registration_number) : null;
	
				$validator = Validator::make($request->all(), [
					'job_order_id' => [
						'required',
						'integer',
						'exists:job_orders,id',
					],
					'is_registered' => [
						'required',
						'integer',
					],
					'registration_number' => [
						'required_if:is_registered,==,1',
						'max:13',
						// 'unique:vehicles,registration_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
					],
					'is_sold' => [
						'required_if:is_registered,==,0',
						'integer',
					],
					'sold_date' => [
						'required_if:is_sold,==,1',
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
						'min:8',
						'max:64',
						'string',
						'unique:vehicles,chassis_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
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
				$request['registration_number'] = $request->registration_number ? str_replace('-', '', $request->registration_number) : null;
				$vehicle = Vehicle::find($request->id);
				$vehicle->updated_by_id = Auth::id();
				$vehicle->updated_at = Carbon::now();
				$vehicle->fill($request->all());
			
				
				$vehicle->save();
	
				DB::commit();
	
				return response()->json([
					'success' => true,
					'message' => 'Vehicle detail saved Successfully!!',
				]);
	
			} catch (Exception $e) {
				return response()->json([
					'success' => false,
					'error' => 'Server Network Down!',
					'errors' => ['Exception Error' => $e->getMessage()],
				]);
			}
		
				

		}else if($request->type_id == 2){//Customer Details
			try {

				DB::beginTransaction();
	
				$validator = Validator::make($request->all(), [
					'ownership_type_id' => [
						'required',
						'integer',
						'exists:configs,id',
						// 'unique:vehicle_owners,ownership_id,' . $request->id . ',customer_id,vehicle_id,' . $vehicle->id,
					],
					// 'code' => [
					//     'required',
					//     'min:3',
					//     'max:255',
					//     'unique:customers,code,' . $request->customer_id . ',id',
					// ],
					'name' => [
						'required',
						'min:3',
						'max:255',
						'string',
					],
					'mobile_no' => [
						'required',
						'min:10',
						'max:10',
						'string',
					],
					'email' => [
						'nullable',
						'string',
						'max:255',
						// 'unique:customers,email,' . $request->customer_id . ',id',
					],
					'address_line1' => [
						'required',
						'min:3',
						// 'max:32',
						'string',
					],
					'address_line2' => [
						'nullable',
						'min:3',
						'max:64',
						'string',
					],
					'country_id' => [
						'required',
						'integer',
						'exists:countries,id',
					],
					'state_id' => [
						'required',
						'integer',
						'exists:states,id',
					],
					'city_id' => [
						'required',
						'integer',
						'exists:cities,id',
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
				]);
	
				if ($validator->fails()) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => $validator->errors()->all(),
					]);
				}
	
				$customer = Customer::saveCustomer($request->all());
			
				$address = Address::firstOrNew([
					'company_id' => Auth::user()->company_id,
					'address_of_id' => 24, //CUSTOMER
					'entity_id' => $customer->id,
					'address_type_id' => 40, //PRIMARY ADDRESS
				]);
				// dd($address);
				$address->fill($request->all());
				$address->save();

				// $vehicle_owner = VehicleOwner::firstOrNew([
                //     'vehicle_id' => $vehicle->id,
                //     'customer_id' => $customer->id,
                // ]);
                // $vehicle_owner->from_date = Carbon::now();
                // $vehicle_owner->updated_by_id = Auth::user()->id;
                // $vehicle_owner->updated_at = Carbon::now();
            

				// $vehicle_owner->customer_id = $customer->id;
				// $vehicle_owner->ownership_id = $request->ownership_type_id;
				// $vehicle_owner->save();
				
	
				DB::commit();
	
				return response()->json([
					'success' => true,
					'message' => 'Customer detail saved Successfully!!',
				]);
	
			} catch (\Exception $e) {
				return response()->json([
					'success' => false,
					'error' => 'Server Error',
					'errors' => [
						'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
					],
				]);
			}



		}else{//Order Details
			try {

				DB::beginTransaction();

				$job_order = JobOrder::find($request->job_order_id);
				if (!$job_order) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Job Order Not Found!',
						],
					]);
				}
				$validator = Validator::make($request->all(), [
					'service_contact_no' => [
						'required',
						'min:10',
						'max:10',
						'string',
					],
					
				]);
			
				$job_order->contact_number = $request->service_contact_no;
				$job_order->save();

				DB::commit();

				return response()->json([
					'success' => true,
					'message' => 'Order Details saved Successfully!!',
				]);

			} catch (\Exception $e) {
				return response()->json([
					'success' => false,
					'error' => 'Server Error',
					'errors' => [
						'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
					],
				]);
			}


		}

	}

}
