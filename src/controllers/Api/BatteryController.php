<?php

namespace Abs\GigoPkg\Api;

use Abs\SerialNumberPkg\SerialNumberGroup;
use App\BatteryLoadTestResult;
use App\BatteryLoadTestStatus;
use App\BatteryMake;
use App\Config;
use App\Country;
use App\Customer;
use App\FinancialYear;
use App\Http\Controllers\Controller;
use App\HydrometerElectrolyteStatus;
use App\LoadTestStatus;
use App\Outlet;
use App\User;
use App\Vehicle;
use App\VehicleBattery;
use App\VehicleOwner;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use App\MultimeterTestStatus;

class BatteryController extends Controller
{
    public $successStatus = 200;

    public function getFormData(Request $request)
    {
        // dd($request->all());
        if ($request->id) {
            $battery = BatteryLoadTestResult::with([
                'vehicleBattery',
                'vehicleBattery.batteryMake',
                'vehicleBattery.customer',
                'vehicleBattery.customer.address',
                'vehicleBattery.customer.address.country',
                'vehicleBattery.customer.address.state',
                'vehicleBattery.customer.address.city',
                'vehicleBattery.vehicle',
                'vehicleBattery.vehicle.model',
                'outlet',
                'batteryLoadTestStatus',
                'loadTestStatus',
                'hydrometerElectrolyteStatus',
                'replacedBatteryMake',
                'batteryNotReplacedReason',
                'multimeterTestStatus',
                'secondbatterymultimeterTestStatus',
                'vehicleBattery.battery_status',
                'vehicleBattery.secondbatteryMake',
                'secondbatteryAmphour',
                'firstbatteryAmphour',
                'firstbatteryvoltage',
                'secondbatteryvoltage',
                'secondbatteryloadTestStatus',
                'secondbatteryhydrometerElectrolyteStatus',
                'secondbatterymultimeterTestStatus',
                'secondreplacedBatteryMake',
                'secondbatteryoverallLoadTestStatus',
            ])->find($request->id);
            $action = 'Edit';

            $user = User::with(['outlet'])->find($battery->created_by_id);

        } else {
            $battery = new BatteryLoadTestResult;
            $action = 'New';

            $user = User::with(['outlet'])->find(Auth::user()->id);
        }

        $this->data['battery'] = $battery;
        $this->data['action'] = $action;

        $this->data['user'] = $user;

        $extras = [
            'battery_list' => collect(BatteryMake::where('company_id', Auth::user()->company_id)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Battery']),
            'battery_load_test_status_list' => collect(BatteryLoadTestStatus::where('company_id', Auth::user()->company_id)->where('id', '!=', 3)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Status']),
            'load_test_result_status_list' => collect(LoadTestStatus::where('company_id', Auth::user()->company_id)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Status']),
            'hydrometer_status_list' => collect(HydrometerElectrolyteStatus::where('company_id', Auth::user()->company_id)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Status']),
            'country_list' => Country::getDropDownList(),
            'state_list' => [], //State::getDropDownList(),
            'city_list' => [], //City::getDropDownList(),
            'country' => Country::find(1),
            'reading_type_list' => Config::getDropDownList([
                'config_type_id' => 33,
                'default_text' => 'Select Reading type',
            ]),
            'battery_not_replace_reasons' => collect(Config::where('config_type_id', 477)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Reason']),
            'replaced_battery_list' => collect(BatteryMake::where('id', 4)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Battery']),
            'amp_hour' => collect(Config::where('config_type_id', 480)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select AMP Hour']),
            'battery_voltage' => collect(Config::where('config_type_id', 479)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Battery Voltage']),
            'multimeter_status_list' => collect(MultimeterTestStatus::where('company_id', Auth::user()->company_id)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Status']),
            'over_all_status_list' => collect(config::where('config_type_id',481)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Status']),
        ];
        $this->data['extras'] = $extras;

        $this->data['success'] = true;

        return response()->json($this->data);

    }

    public function paymentSave(Request $request)
    {
        // dd($request->all());
        try {

            $validator = Validator::make($request->all(), [
                'battery_id' => [
                    'required',
                ],
                'invoice_number' => [
                    'required',
                    'unique:battery_load_test_results,invoice_number,' . $request->battery_id . ',id',
                ],
                'invoice_date' => [
                    'required',
                ],
                // 'invoice_amount' => [
                //     'required',
                // ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => $validator->errors()->all(),
                ]);
            }

            DB::beginTransaction();

            $battery_result = BatteryLoadTestResult::find($request->battery_id);

            $battery_result->invoice_number = $request->invoice_number;
            // $battery_result->invoice_date = date('Y-m-d', strtotime($request->invoice_date));
            // $battery_result->invoice_amount = $request->invoice_amount;
            $battery_result->updated_by_id = Auth::user()->id;
            $battery_result->updated_at = Carbon::now();

            $battery_result->save();

            DB::commit();

            $message = 'Battery Invoice Details Saved Successfully!';

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Server Error',
                'errors' => [
                    'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
                ],
            ]);
        }
    }

    public function save(Request $request)
    {
        //    dd($request->all());
        try {

            $error_messages = [
                'battery_serial_number.required_if' => "Battery Serial Number required",
                'load_test_status_id.required' => "Load Test Status required",
                'hydrometer_electrolyte_status_id.required' => "Hydrometer Electrolyte Status required",
            ];

            $validator = Validator::make($request->all(), [
                'registration_number' => [
                    'required',
                    'max:13',
                ],
                'chassis_number' => [
                    'required',
                ],
                'engine_number' => [
                    'required',
                ],
                'model_id' => [
                    'required',
                    'integer',
                    'exists:models,id',
                ],
                'km_reading_type_id' => [
                    'required',
                    'integer',
                    'exists:configs,id',
                ],
                'km_reading' => [
                    'required_if:km_reading_type_id,==,8040',
                    'numeric',
                ],
                'hr_reading' => [
                    'required_if:km_reading_type_id,==,8041',
                    'numeric',
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
                'battery_make_id' => [
                    'required',
                    'integer',
                    'exists:battery_makes,id',
                ],
                'battery_serial_number' => [
                    // 'required_if:overall_status_id,==,3',
                    'required_if:is_buy_back_opted,==,1',
                ],
                // 'amp_hour' => [
                //     'required',
                // ],
                // 'battery_voltage' => [
                //     'required',
                // ],
                'load_test_status_id' => [
                    'required',
                    'integer',
                    'exists:load_test_statuses,id',
                ],
                'hydrometer_electrolyte_status_id' => [
                    'required',
                    'integer',
                    'exists:hydrometer_electrolyte_statuses,id',
                ],
                'overall_status_id' => [
                    'required',
                    'integer',
                    'exists:battery_load_test_statuses,id',
                ],
                // 'remarks' => [
                //     'required',
                // ],
                'battery_not_replaced_reason_id' => [
                    'required_if:is_battery_replaced,==,0',
                ],
                'second_battery_serial_number' => [
                    // 'required_if:overall_status_id,==,3',
                    'required_if:is_second_battery_buy_back_opted,==,1',
                ],

            ], $error_messages);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => $validator->errors()->all(),
                ]);
            }

            DB::beginTransaction();

            $registration_number = $request->registration_number ? str_replace([' ', '-'], '', $request->registration_number) : null;
            //New
            if ($registration_number) {
                $vehicle = Vehicle::where([
                    'company_id' => Auth::user()->company_id,
                    'registration_number' => $registration_number,
                ])->first();

                if (!$vehicle) {
                    //Chassis Number
                    if ($request->chassis_number) {
                        $vehicle = Vehicle::firstOrNew([
                            'company_id' => Auth::user()->company_id,
                            'chassis_number' => $request->chassis_number,
                        ]);
                    }
                    //Engine Number
                    else {
                        $vehicle = Vehicle::firstOrNew([
                            'company_id' => Auth::user()->company_id,
                            'engine_number' => $request->engine_number,
                        ]);
                    }
                }
            } else {
                //Chassis Number
                if ($request->chassis_number) {
                    $vehicle = Vehicle::firstOrNew([
                        'company_id' => Auth::user()->company_id,
                        'chassis_number' => $request->chassis_number,
                    ]);
                }
                //Engine Number
                else {
                    $vehicle = Vehicle::firstOrNew([
                        'company_id' => Auth::user()->company_id,
                        'engine_number' => $request->engine_number,
                    ]);
                }
            }

            if (!$vehicle->exists) {
                $vehicle->company_id = Auth::user()->company_id;
                $vehicle->created_by_id = Auth::user()->id;
                $vehicle->created_at = Carbon::now();

            } else {
                $vehicle->updated_by_id = Auth::user()->id;
                $vehicle->updated_at = Carbon::now();
            }

            $vehicle->registration_number = $registration_number;

            if ($request->km_reading_type_id == 8040) {
                $vehicle->km_reading_type_id = 8040;
                $vehicle->km_reading = $request->km_reading;
            } else {
                $vehicle->km_reading_type_id = 8041;
                $vehicle->hr_reading = $request->hr_reading;
            }

            if ($request->sold_date) {
                $vehicle->sold_date = date('Y-m-d', strtotime($request->sold_date));
            }

            $vehicle->engine_number = $request->engine_number;
            $vehicle->chassis_number = $request->chassis_number;
            $vehicle->model_id = $request->model_id;
            $vehicle->save();

            //Save Customer
            $customer = Customer::saveCustomer($request->all());
            $customer->saveAddress($request->all());

            if ($customer) {
                $vehicle_owner = VehicleOwner::firstornew(['vehicle_id' => $vehicle->id, 'customer_id' => $customer->id]);

                $ownership_count = VehicleOwner::where('vehicle_id', $vehicle->id)->count();

                if ($vehicle_owner->exists) {
                    //Check last owner is same custmer or not
                    $last_vehicle_owner = VehicleOwner::where('vehicle_id', $vehicle->id)->orderBy('ownership_id', 'DESC')->first();

                    if ($last_vehicle_owner->customer_id != $customer->id) {
                        $ownership_id = $last_vehicle_owner->ownership_id + 1;
                        $vehicle_owner->ownership_id = $ownership_id;
                        $vehicle_owner->from_date = Carbon::now();
                    }

                    $vehicle_owner->updated_at = Carbon::now();
                } else {
                    $ownership_id = 8160 + $ownership_count;
                    $vehicle_owner->ownership_id = $ownership_id;
                    $vehicle_owner->from_date = Carbon::now();
                    $vehicle_owner->created_at = Carbon::now();
                }
                $vehicle_owner->save();

                $vehicle->customer_id = $customer->id;
                $vehicle->save();
            }

            $manufactured_date = date('Y-m-d', strtotime($request->manufactured_date));
            if($request->no_of_batteries == 2){
                $manufactured_date_2 = date('Y-m-d', strtotime($request->manufactured_date_2));//Battery - 2 
             }else{
                $manufactured_date_2 = null;
             }

            $vehicle_battery = VehicleBattery::firstOrNew([
                'company_id' => Auth::user()->company_id,
                'business_id' => 16,
                'vehicle_id' => $vehicle->id,
                'customer_id' => $customer->id,
                'battery_make_id' => $request->battery_make_id,
                'manufactured_date' => $manufactured_date,
                'battery_status_id' => $request->battery_status_id,
                'outlet_id' => Auth::user()->employee->outlet_id,
                'second_battery_make_id'=> $request->second_battery_make_id ? $request->second_battery_make_id : null,
                'second_battery_manufactured_date' =>  $manufactured_date_2,
            ]);

            if ($vehicle_battery->exists) {
                $vehicle_battery->updated_by_id = Auth::user()->id;
                $vehicle_battery->updated_at = Carbon::now();
            } else {
                $vehicle_battery->created_by_id = Auth::user()->id;
                $vehicle_battery->created_at = Carbon::now();
                $vehicle_battery->updated_at = null;
            }

            $vehicle_battery->battery_serial_number = isset($request->battery_serial_number) ? $request->battery_serial_number : null;
           
            $vehicle_battery->second_battery_serial_number = isset($request->second_battery_serial_number) ? $request->second_battery_serial_number : null;
           
            //To save job card details in vehilce_battery
            if($request->is_buy_back_opted ||$request->is_second_battery_buy_back_opted){
                $vehicle_battery->job_card_number =$request->job_card_number;
                $vehicle_battery->job_card_date =date('Y-m-d', strtotime($request->job_card_date));
            }else{
                $vehicle_battery->job_card_number = null;
                $vehicle_battery->job_card_date = null;
            }
            $vehicle_battery->remarks = $request->over_all_status_remarks;
            
            $vehicle_battery->save();
      
            if ($request->id) {
                $battery_result = BatteryLoadTestResult::find($request->id);
                $battery_result->updated_by_id = Auth::user()->id;
                $battery_result->updated_at = Carbon::now();
            } else {
                $battery_result = new BatteryLoadTestResult;
                $battery_result->created_by_id = Auth::user()->id;
                $battery_result->created_at = Carbon::now();
                $battery_result->updated_at = null;

                //Serial Number
                if (date('m') > 3) {
                    $year = date('Y') + 1;
                } else {
                    $year = date('Y');
                }
                //GET FINANCIAL YEAR ID
                $financial_year = FinancialYear::where('from', $year)
                    ->where('company_id', Auth::user()->company_id)
                    ->first();
                if (!$financial_year) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'Fiancial Year Not Found',
                        ],
                    ]);
                }
                //GET BRANCH/OUTLET
                $branch = Outlet::where('id', Auth::user()->employee->outlet_id)->first();

                //GENERATE JOB ORDER NUMBER
                $generateJONumber = SerialNumberGroup::generateNumber(164, $financial_year->id, $branch->state_id, $branch->id);
                if (!$generateJONumber['success']) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'No Battery Serial number found for FY : ' . $financial_year->from . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
                        ],
                    ]);
                }

                $error_messages_2 = [
                    'number.required' => 'Serial number is required',
                    'number.unique' => 'Serial number is already taken',
                ];

                $validator_2 = Validator::make($generateJONumber, [
                    'number' => [
                        'required',
                        'unique:battery_load_test_results,number,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
                    ],
                ], $error_messages_2);

                if ($validator_2->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator_2->errors()->all(),
                    ]);
                }

                $battery_result->number = $generateJONumber['number'];
            }

            $battery_result->company_id = Auth::user()->company_id;
            $battery_result->outlet_id = Auth::user()->working_outlet_id;
            $battery_result->vehicle_battery_id = $vehicle_battery->id;
            $battery_result->load_test_status_id = $request->load_test_status_id;
            $battery_result->hydrometer_electrolyte_status_id = $request->hydrometer_electrolyte_status_id;
            $battery_result->overall_status_id = $request->overall_status_id;
            $battery_result->first_battery_amp_hour_id = $request->first_battery_amp_hour_id;
            $battery_result->multimeter_test_status_id = $request->multimeter_test_status_id;
            $battery_result->first_battery_battery_voltage_id = $request->first_battery_battery_voltage_id;
            //Battery - 2
            $battery_result->second_battery_battery_voltage_id = $request->second_battery_battery_voltage_id;
            $battery_result->second_battery_amp_hour_id = $request->second_battery_amp_hour_id;
            $battery_result->second_battery_multimeter_test_status_id = $request->second_battery_multimeter_test_status_id;
            $battery_result->second_battery_load_test_status_id = $request->second_battery_load_test_status_id;
            $battery_result->second_battery_hydrometer_electrolyte_status_id=$request->second_battery_hydrometer_electrolyte_status_id;
            $battery_result->second_battery_overall_status_id= $request->second_battery_overall_status_id;
            // No of Batteries
            $battery_result->no_of_batteries = $request->no_of_batteries;

            if ($request->is_battery_replaced == 1) {
                $battery_result->is_battery_replaced = $request->is_battery_replaced;
                $battery_result->replaced_battery_make_id = $request->replaced_battery_make_id;
                $battery_result->replaced_battery_serial_number = $request->replaced_battery_serial_number;
                $battery_result->is_buy_back_opted = $request->is_buy_back_opted;
                $battery_result->battery_not_replaced_reason_id = null;
                $battery_result->job_card_number = $request->job_card_number;
            } else {
                $battery_result->is_battery_replaced = 0;
                $battery_result->replaced_battery_make_id = null;
                $battery_result->replaced_battery_serial_number = null;
                $battery_result->is_buy_back_opted = null;
                $battery_result->battery_not_replaced_reason_id = $request->battery_not_replaced_reason_id;
                $battery_result->job_card_number = 1000;//for testing
            }
            //Battery - 2
            if($request->is_second_battery_replaced == 1){
               $battery_result->is_second_battery_replaced = $request->is_second_battery_replaced;
               $battery_result->replaced_second_battery_make_id = $request->replaced_second_battery_make_id;
               $battery_result->replaced_second_battery_serial_number = $request->replaced_second_battery_serial_number;
               $battery_result->is_second_battery_buy_back_opted = $request->is_second_battery_buy_back_opted;
               $battery_result->second_battery_not_replaced_reason_id =  null;
            }else{
                $battery_result->is_second_battery_replaced = 0;
                $battery_result->replaced_second_battery_make_id = null;
                $battery_result->replaced_second_battery_serial_number = null;
                $battery_result->is_second_battery_buy_back_opted = null;
                $battery_result->second_battery_not_replaced_reason_id = $request->battery_not_replaced_reason_id;
            }
            $battery_result->invoice_number = 100;//for testing
            $battery_result->save();

            DB::commit();

            $message = 'Battery Details Saved Successfully!';

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (Exception $e) {
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
