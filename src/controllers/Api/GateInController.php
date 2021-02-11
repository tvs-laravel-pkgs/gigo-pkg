<?php

namespace Abs\GigoPkg\Api;

use Abs\AmcPkg\AmcPolicy;
use Abs\GigoPkg\AmcMember;
use Abs\GigoPkg\ModelType;
use Abs\GigoPkg\TradePlateNumber;
use Abs\SerialNumberPkg\SerialNumberGroup;
use App\Attachment;
use App\Config;
use App\Customer;
use App\Employee;
use App\Entity;
use App\FinancialYear;
use App\FloatingGatePass;
use App\GateLog;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WpoSoapController;
use App\JobOrder;
use App\Jobs\Notification;
use App\Outlet;
use App\ShortUrl;
use App\Vehicle;
use App\VehicleOwner;
use App\VehicleInventoryItem;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Storage;
use Validator;
use Yajra\Datatables\Datatables;

class GateInController extends Controller {
	public $successStatus = 200;

	public function __construct(WpoSoapController $getSoap = null) {
		$this->data['theme'] = config('custom.theme');
		$this->getSoap = $getSoap;
		$this->success_code = 200;
		$this->permission_denied_code = 401;
	}

	public function getFormData() {
		try {

			$params['field_type_id'] = [11, 12];

			$extras = [
				'reading_type_list' => Config::getDropDownList([
					'config_type_id' => 33,
					'default_text' => 'Select Reading type',
				]),
				'inventory_type_list' => VehicleInventoryItem::getInventoryList($job_order_id = NULL, $params),
				'trade_plate_number_list' => TradePlateNumber::join('outlets', 'outlets.id', 'trade_plate_numbers.outlet_id')->select('trade_plate_numbers.id', DB::RAW('CONCAT(outlets.code," / ",trade_plate_numbers.trade_plate_number) as trade_plate_number'))->get(),
				'upload_image_ratio' => 80,
			];
			return response()->json([
				'success' => true,
				'extras' => $extras,
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

	public function createGateInEntry(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			//REMOVE WHITE SPACE BETWEEN REGISTRATION NUMBER
			$request->registration_number = str_replace(' ', '', $request->registration_number);

			//REGISTRATION NUMBER VALIDATION
			$error = '';
			if ($request->registration_number) {
				$regis_number = $request->registration_number;
				$registration_no_count = strlen($request->registration_number);
				if ($registration_no_count < 8) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'The registration number must be at least 8 characters.',
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
			} else {
				$regis_number = '-';
			}

			//REMOVE - INBETWEEN REGISTRATION NUMBER
			$request['registration_number'] = $request->registration_number ? str_replace('-', '', $request->registration_number) : NULL;

			if (!$request->chassis_number && !$request->engine_number) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						"Please enter Chassis / Engine number!",
					],
				]);
			}

			$validator = Validator::make($request->all(), [
				'vehicle_photo' => [
					'required',
					'mimes:jpeg,jpg,png',
					// 'max:3072',
				],
				'km_reading_photo' => [
					'required',
					'mimes:jpeg,jpg,png',
					// 'max:3072',
				],
				'driver_photo' => [
					'required',
					'mimes:jpeg,jpg,png',
					// 'max:3072',
				],
				'chassis_photo' => [
					'required',
					'mimes:jpeg,jpg,png',
					// 'max:3072',
				],
				'is_registered' => [
					'required',
					'integer',
				],
				'registration_number' => [
					'required_if:is_registered,==,1',
					'max:13',
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
				'driver_name' => [
					'nullable',
					'min:3',
					'max:64',
					'string',
				],
				'driver_mobile_number' => [
					'nullable',
					'min:10',
					'max:10',
					'string',
				],
				'gate_in_remarks' => [
					'nullable',
					'max:191',
					'string',
				],
				'vehicle_inventory_items.*.is_available' => [
					'required',
					'numeric',
				],
				'vehicle_inventory_items.*.remarks' => [
					'nullable',
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

			//Check Driver & Security Signature
			if ($request->web == 'website') {
				// $validator = Validator::make($request->all(), [
				// 	'driver_signature' => [
				// 		'required',
				// 	],
				// 	'security_signature' => [
				// 		'required',
				// 	],
				// ]);
			} else {
				// $validator = Validator::make($request->all(), [
				// 	'security_signature' => [
				// 		'required',
				// 		'mimes:jpeg,jpg',
				// 	],
				// 	'driver_signature' => [
				// 		'required',
				// 		'mimes:jpeg,jpg',
				// 	],
				// ]);
			}

			if ($request->search_type == 1 && $request->vehicle_id) {
				//Exisiting
				$vehicle = Vehicle::find($request->vehicle_id);
				$vehicle_form_filled = 1;
				if ($vehicle->currentOwner) {
					$customer_form_filled = 1;
					$vehicle->status_id = 8142; //COMPLETED
				} else {
					$customer_form_filled = 0;
					$vehicle->status_id = 8141; //CUSTOMER NOT MAPPED
				}
				$vehicle->registration_number = $request->registration_number ? str_replace('-', '', $request->registration_number) : NULL;
				$vehicle->chassis_number = $request->chassis_number;
				$vehicle->engine_number = $request->engine_number;
				$vehicle->driver_name = $request->driver_name;
				$vehicle->driver_mobile_number = $request->driver_mobile_number;
				$vehicle->updated_by_id = Auth::user()->id;
				$vehicle->updated_at = Carbon::now();

				$vehicle_form_filled = 1;
				if ($vehicle->currentOwner) {
					$customer_form_filled = 1;
					$vehicle->status_id = 8142; //COMPLETED
				} else {
					$customer_form_filled = 0;
					$vehicle->status_id = 8141; //CUSTOMER NOT MAPPED
				}

				$vehicle->save();
			} else {
				$registration_number = $request->registration_number ? str_replace('-', '', $request->registration_number) : NULL;
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

				//NEW
				if (!$vehicle->exists) {
					$vehicle_form_filled = 0;
					$customer_form_filled = 0;
					$vehicle->status_id = 8140; //NEW
					$vehicle->company_id = Auth::user()->company_id;
					$vehicle->created_by_id = Auth::user()->id;
				} else {
					$vehicle_form_filled = 1;
					if ($vehicle->currentOwner) {
						$customer_form_filled = 1;
						$vehicle->status_id = 8142; //COMPLETED
					} else {
						$customer_form_filled = 0;
						$vehicle->status_id = 8141; //CUSTOMER NOT MAPPED
					}
					$vehicle->updated_by_id = Auth::user()->id;
				}

				$vehicle->fill($request->all());
				$vehicle->registration_number = $registration_number;
				$vehicle->save();
				$request->vehicle_id = $vehicle->id;
			}

			//Check Floating GatePass
			$floating_gate_pass = FloatingGatePass::join('job_cards', 'job_cards.id', 'floating_stock_logs.job_card_id')->join('job_orders', 'job_orders.id', 'job_cards.job_order_id')->where('floating_stock_logs.status_id', 11161)->where('job_orders.vehicle_id', $vehicle->id)->first();
			if ($floating_gate_pass) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Floating Parts Gate Out not completed on this Vehicle!',
					],
				]);
			}

			//CHECK VEHICLE PREVIOUS JOBCARD STATUS
			$previous_job_order = JobOrder::where('vehicle_id', $vehicle->id)->orderBy('id', 'DESC')->first();

			if ($previous_job_order) {
				if ($previous_job_order->status_id != 8470 && $previous_job_order->status_id != 8476 && $previous_job_order->status_id != 8467 && $previous_job_order->status_id != 8468 && $previous_job_order->status_id != '') {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Previous Job Order not completed on this Vehicle!',
						],
					]);
				}
			}

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

			//Check Floating GatePass
			$floating_gate_pass = FloatingGatePass::join('job_cards', 'job_cards.id', 'floating_stock_logs.job_card_id')->join('job_orders', 'job_orders.id', 'job_cards.job_order_id')->where('floating_stock_logs.status_id', 11162)->where('job_orders.vehicle_id', $vehicle->id)->select('job_orders.id as job_order_id', 'job_orders.outlet_id')->first();

			$request['vehicle_id'] = $vehicle->id;

			$generate_number_status = 0;
			if ($floating_gate_pass && $floating_gate_pass->job_order_id) {
				$job_order = JobOrder::find($floating_gate_pass->job_order_id);

				if ($job_order && ($job_order->outlet_id != Auth::user()->employee->outlet_id)) {
					$job_order = new JobOrder;
					$job_order->company_id = Auth::user()->company_id;
					$job_order->number = rand();
					$job_order->vehicle_id = $vehicle->id;
					$job_order->outlet_id = Auth::user()->employee->outlet_id;
					if ($vehicle->currentOwner) {
						$job_order->customer_id = $vehicle->currentOwner->customer_id;
					}
					$job_order->save();
					$generate_number_status = 1;
				}
			} else {
				//Get vehicle recent service date
				// $job_order = JobOrder::where('vehicle_id', $vehicle->id)->orderBy('id', 'DESC')->first();
				$job_card_status = 1; // Create New
				// if ($job_order) {
				// 	$previous_job_date = $job_order->created_at;

				// 	$job_card_reopen_date = Entity::where('entity_type_id', 35)->select('name')->first();
				// 	if ($job_card_reopen_date) {
				// 		$job_card_reopen_date = date("d-m-Y h:i A", strtotime('+' . $job_card_reopen_date->name . ' days', strtotime($job_order->created_at)));
				// 	} else {
				// 		$job_card_reopen_date = date("d-m-Y h:i A", strtotime('+60 days', strtotime($job_order->created_at)));
				// 	}

				// 	$job_card_reopen_date = date('Ymdhi', strtotime($job_card_reopen_date));

				// 	$current_date = date('Ymdhi');

				// 	if ($job_card_reopen_date > $current_date) {
				// 		$job_card_status = 2; // Reopen Last JobOrder
				// 	} else {
				// 		$job_card_status = 1; // Create New JobOrder
				// 	}
				// }

				if ($job_card_status == 1) {
					$job_order = new JobOrder;
					$job_order->company_id = Auth::user()->company_id;
					$job_order->number = rand();
					$job_order->vehicle_id = $vehicle->id;
					$job_order->outlet_id = Auth::user()->employee->outlet_id;
					if ($vehicle->currentOwner) {
						$job_order->customer_id = $vehicle->currentOwner->customer_id;
					}
					$job_order->save();

					$generate_number_status = 1;
				}
			}

			if ($generate_number_status == 1) {
				//GENERATE JOB ORDER NUMBER
				$generateJONumber = SerialNumberGroup::generateNumber(21, $financial_year->id, $branch->state_id, $branch->id);
				if (!$generateJONumber['success']) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'No Job Order Serial number found for FY : ' . $financial_year->from . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
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
						'unique:job_orders,number,' . $job_order->id . ',id,company_id,' . Auth::user()->company_id,
					],
				], $error_messages_2);

				if ($validator_2->fails()) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => $validator_2->errors()->all(),
					]);
				}

				$job_order->number = $generateJONumber['number'];
				$job_order->save();
			}

			$job_order->fill($request->all());
			$job_order->gatein_trade_plate_number_id = $request->trade_plate_number ? $request->trade_plate_number : NULL;
			$job_order->service_advisor_id = NULL;
			$job_order->status_id = 8460; //Ready for Inward
			$job_order->save();

			//NEW GATE IN ENTRY
			$gate_log = new GateLog;
			$gate_log->fill($request->all());
			$gate_log->company_id = Auth::user()->company_id;
			$gate_log->job_order_id = $job_order->id;
			$gate_log->created_by_id = Auth::user()->id;
			$gate_log->gate_in_date = Carbon::now();
			$gate_log->status_id = 8120; //GATE IN COMPLETED
			$gate_log->outlet_id = Auth::user()->employee->outlet_id;
			$gate_log->save();

			//GENERATE GATE IN VEHICLE NUMBER
			$generateNumber = SerialNumberGroup::generateNumber(20, $financial_year->id, $branch->state_id, $branch->id);
			if (!$generateNumber['success']) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'No Gate In Serial number found for FY : ' . $financial_year->from . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
					],
				]);
			}

			$error_messages_1 = [
				'number.required' => 'Serial number is required',
				'number.unique' => 'Serial number is already taken',
			];

			$validator_1 = Validator::make($generateNumber, [
				'number' => [
					'required',
					'unique:gate_logs,number,' . $gate_log->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages_1);

			if ($validator_1->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator_1->errors()->all(),
				]);
			}
			$gate_log->number = $generateNumber['number'];
			$gate_log->save();

			//CREATE DIRECTORY TO STORAGE PATH
			$attachment_path = storage_path('app/public/gigo/gate_in/attachments/');
			Storage::makeDirectory($attachment_path, 0777);

			//SAVE VEHICLE PHOTO ATTACHMENT
			if (!empty($request->vehicle_photo)) {
				$attachment = $request->vehicle_photo;
				$entity_id = $gate_log->id;
				$attachment_of_id = 225; //GATE LOG
				$attachment_type_id = 247; //VEHICLE PHOTO
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}

			//SAVE KM READING PHOTO
			if (!empty($request->km_reading_photo)) {
				$attachment = $request->km_reading_photo;
				$entity_id = $gate_log->id;
				$attachment_of_id = 225; //GATE LOG
				$attachment_type_id = 248; //KM READING PHOTO
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}

			//SAVE DRIVER PHOTO
			if (!empty($request->driver_photo)) {
				$attachment = $request->driver_photo;
				$entity_id = $gate_log->id;
				$attachment_of_id = 225; //GATE LOG
				$attachment_type_id = 249; //DRIVER PHOTO
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}

			//SAVE DRIVER PHOTO
			if (!empty($request->chassis_photo)) {
				$attachment = $request->chassis_photo;
				$entity_id = $gate_log->id;
				$attachment_of_id = 225; //GATE LOG
				$attachment_type_id = 236; //CHASSIS PHOTO
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}

			if ($generate_number_status == 1) {
				//INWARD PROCESS CHECK
				$inward_mandatory_tabs = Config::getDropDownList([
					'config_type_id' => 122,
					'orderBy' => 'id',
					'add_default' => false,
				]);
				$job_order->inwardProcessChecks()->sync([]);
				if (!empty($inward_mandatory_tabs)) {
					foreach ($inward_mandatory_tabs as $key => $inward_mandatory_tab) {
						//VEHICLE DETAILS TAB
						if ($inward_mandatory_tab->id == 8700) {
							$is_form_filled = $vehicle_form_filled;
						} elseif ($inward_mandatory_tab->id == 8701) {
							//CUSTOMER DETAILS TAB
							$is_form_filled = $customer_form_filled;
						} else {
							$is_form_filled = 0;
						}
						$job_order->inwardProcessChecks()->attach($inward_mandatory_tab->id, [
							'is_form_filled' => $is_form_filled,
						]);
					}
				}
			}

			$job_order->vehicle_delivery_status_id = 1;
			if ($vehicle->currentOwner) {
				$job_order->customer_id = $vehicle->currentOwner->customer_id;
			}
			$job_order->save();
			// $job_order->vehicleInventoryItem()->sync([]);
			//Remove already saved gatelog inventories
			$inventories = DB::table('job_order_vehicle_inventory_item')->where('gate_log_id', $gate_log->id)->delete();

			if ($request->vehicle_inventory_items) {
				foreach ($request->vehicle_inventory_items as $key => $vehicle_inventory_item) {
					if (isset($vehicle_inventory_item['inventory_item_id']) && $vehicle_inventory_item['is_available'] == 1) {
						$job_order->vehicleInventoryItem()
							->attach(
								$vehicle_inventory_item['inventory_item_id'],
								[
									'is_available' => 1,
									'remarks' => $vehicle_inventory_item['remarks'],
									'gate_log_id' => $gate_log->id,
								]
							);
					}
				}
			}

			if ($regis_number != '-') {
				$number = $regis_number;
				$soap_number = str_replace('-', '', $number);
				$gate_in_data['registration_number'] = $regis_number;
				$gate_in_data['label'] = 'Reg No';
			} else {
				if ($vehicle->chassis_number) {
					$gate_in_data['registration_number'] = $vehicle->chassis_number;
					$number = $vehicle->chassis_number;
					$soap_number = $vehicle->chassis_number;
					$gate_in_data['label'] = 'Chassis No';
				} else {
					$gate_in_data['registration_number'] = $vehicle->engine_number;
					$number = $vehicle->engine_number;
					$gate_in_data['label'] = 'Engine No';
					$soap_number = $vehicle->engine_number;
				}
			}

			$membership_data = $this->getSoap->GetTVSONEVehicleDetails($soap_number);
			// dd($membership_data);
			$membership_message = '';
			if ($membership_data && $membership_data['success'] == 'true') {
				// dump($membership_data);

				$amc_policy = AmcPolicy::firstOrNew(['company_id' => Auth::user()->company_id, 'name' => $membership_data['membership_name'], 'type' => $membership_data['membership_type']]);
				if ($amc_policy->exists) {
					$amc_policy->updated_by_id = Auth::user()->id;
					$amc_policy->updated_at = Carbon::now();
				} else {
					$amc_policy->created_by_id = Auth::user()->id;
					$amc_policy->created_at = Carbon::now();
				}

				$amc_policy->save();

				$amc_member = AmcMember::firstOrNew(['company_id' => Auth::user()->company_id, 'entity_type_id' => 11180, 'vehicle_id' => $vehicle->id, 'policy_id' => $amc_policy->id, 'number' => $membership_data['membership_number']]);

				if ($amc_member->exists) {
					$amc_member->updated_by_id = Auth::user()->id;
					$amc_member->updated_at = Carbon::now();
				} else {
					$amc_member->created_by_id = Auth::user()->id;
					$amc_member->created_at = Carbon::now();
				}

				$amc_member->start_date = date('Y-m-d', strtotime($membership_data['start_date']));
				$amc_member->expiry_date = date('Y-m-d', strtotime($membership_data['end_date']));

				$amc_member->save();

				$job_order->service_policy_id = $amc_member->id;
				$job_order->save();

				$membership_message = '. TVS Membership Number is ' . $membership_data['membership_number'];

			}
			
			//Check Customer Mapped this vehicle or not
			if (!$vehicle->currentOwner) {
				// dd($vehicle);
				if($vehicle->registration_number){
					$key = str_replace('-','',$vehicle->registration_number);
				}else if($vehicle->chassis_number){
					$key = $vehicle->chassis_number;
				}else{
					$key = $vehicle->engine_number;
				}
				$vehicle_data = $this->getSoap->GetVehicleDetails($key);
				if ($vehicle_data && $vehicle_data['success'] == 'true') {
					// dump($vehicle_data);
					$vehicle->registration_number = isset($vehicle_data['vehicle_reg_number']) ? $vehicle_data['vehicle_reg_number'] : NULL;
					$vehicle->chassis_number = $vehicle_data['chassis_number'];
					$vehicle->engine_number = $vehicle_data['engine_number'];

					//Save Customer
					$customer = null;
					if(isset($vehicle_data['al_dms_code'])){
						$customer = Customer::where('code',ltrim($vehicle_data['al_dms_code'], '0'))->first();
						if($customer){
							$vehicle->customer_id = $customer->id;
							$vehicle->is_sold = 1;
						}else{
							$vehicle->is_sold = 0;
						}
					}
					$vehicle->sold_date = date('Y-m-d',strtotime($vehicle_data['vehicle_sales_date']));
					$vehicle->save();

					//Save Vehicle Owner
					if($customer)
					{
						$vehicle_owner = VehicleOwner::firstornew(['vehicle_id' => $vehicle->id, 'customer_id' => $customer->id]);
						
						$ownership_count = VehicleOwner::where('vehicle_id', $vehicle->id)->count();

						if ($vehicle_owner->exists) {
							//Check last owner is same custmer or not
							$last_vehicle_owner = VehicleOwner::where('vehicle_id', $vehicle->id)->orderBy('ownership_id', 'DESC')->first();

							if ($last_vehicle_owner->customer_id != $customer->id) {
								$ownership_id = $last_vehicle_owner->ownership_id + 1;
								$vehicle_owner->ownership_id = $ownership_id;
							}

							$vehicle_owner->from_date = Carbon::now();
							$vehicle_owner->updated_at = Carbon::now();
						} else {
							$ownership_id = 8160 + $ownership_count;
							$vehicle_owner->ownership_id = $ownership_id;
							$vehicle_owner->from_date = Carbon::now();
							$vehicle_owner->created_at = Carbon::now();
						}
						$vehicle_owner->save();
						$job_order->customer_id = $vehicle->currentOwner->customer_id;
						$job_order->save();
					}
				}
			}

			$url = url('/') . '/vehicle/track/' . $job_order->id;

			$short_url = ShortUrl::createShortLink($url, $maxlength = "8");

			$tracking_message = 'Greetings from TVS & Sons! Kindly click on this link to track vehicle service status: ' . $short_url;

			//Save Driver & Security Signature
			if ($request->web == 'website') {
				//DRIVER E SIGN
				if (!empty($request->driver_signature)) {
					$remove_previous_attachment = Attachment::where([
						'entity_id' => $job_order->id,
						'attachment_of_id' => 227,
						'attachment_type_id' => 10098,
					])->forceDelete();

					$driver_sign = str_replace('data:image/png;base64,', '', $request->driver_signature);
					$driver_sign = str_replace(' ', '+', $driver_sign);

					$user_images_des = storage_path('app/public/gigo/job_order/attachments/');
					File::makeDirectory($user_images_des, $mode = 0777, true, true);

					$filename = $job_order->id . "webcam_gate_in_driver_sign_" . strtotime("now") . ".png";

					File::put($attachment_path . $filename, base64_decode($driver_sign));

					//SAVE ATTACHMENT
					$attachment = new Attachment;
					$attachment->attachment_of_id = 227; //JOB ORDER
					$attachment->attachment_type_id = 10098; //GateIn Driver Signature
					$attachment->entity_id = $job_order->id;
					$attachment->name = $filename;
					$attachment->created_by = Auth()->user()->id;
					$attachment->created_at = Carbon::now();
					$attachment->save();
				}

				//SECURITY E SIGN
				if (!empty($request->security_signature)) {
					$remove_previous_attachment = Attachment::where([
						'entity_id' => $job_order->id,
						'attachment_of_id' => 227,
						'attachment_type_id' => 10098,
					])->forceDelete();

					$security_sign = str_replace('data:image/png;base64,', '', $request->security_signature);
					$security_sign = str_replace(' ', '+', $security_sign);

					$user_images_des = storage_path('app/public/gigo/job_order/attachments/');
					File::makeDirectory($user_images_des, $mode = 0777, true, true);

					$filename = $job_order->id . "webcam_gate_in_security_sign_" . strtotime("now") . ".png";

					File::put($attachment_path . $filename, base64_decode($security_sign));

					//SAVE ATTACHMENT
					$attachment = new Attachment;
					$attachment->attachment_of_id = 227; //JOB ORDER
					$attachment->attachment_type_id = 10099; //GateIn Security Signature
					$attachment->entity_id = $job_order->id;
					$attachment->name = $filename;
					$attachment->created_by = Auth()->user()->id;
					$attachment->created_at = Carbon::now();
					$attachment->save();
				}

			} else {
				if (!empty($request->driver_signature)) {
					$remove_previous_attachment = Attachment::where([
						'entity_id' => $job_order->id,
						'attachment_of_id' => 227,
						'attachment_type_id' => 10098,
					])->forceDelete();

					$image = $request->driver_signature;
					$time_stamp = date('Y_m_d_h_i_s');
					$extension = $image->getClientOriginalExtension();
					$name = $job_order->id . '_' . $time_stamp . '_gatein_driver_signature.' . $extension;
					$image->move(storage_path('app/public/gigo/job_order/attachments/'), $name);

					//SAVE ATTACHMENT
					$attachment = new Attachment;
					$attachment->attachment_of_id = 227; //JOB ORDER
					$attachment->attachment_type_id = 10098; //GateIn Driver Signature
					$attachment->entity_id = $job_order->id;
					$attachment->name = $name;
					$attachment->created_by = Auth()->user()->id;
					$attachment->created_at = Carbon::now();
					$attachment->save();
				}
				if (!empty($request->security_signature)) {
					$remove_previous_attachment = Attachment::where([
						'entity_id' => $job_order->id,
						'attachment_of_id' => 227,
						'attachment_type_id' => 10099,
					])->forceDelete();

					$image = $request->security_signature;
					$time_stamp = date('Y_m_d_h_i_s');
					$extension = $image->getClientOriginalExtension();
					$name = $job_order->id . '_' . $time_stamp . '_gatein_security_signature.' . $extension;
					$image->move(storage_path('app/public/gigo/job_order/attachments/'), $name);

					//SAVE ATTACHMENT
					$attachment = new Attachment;
					$attachment->attachment_of_id = 227; //JOB ORDER
					$attachment->attachment_type_id = 10099; //GateIn Security Signature
					$attachment->entity_id = $job_order->id;
					$attachment->name = $name;
					$attachment->created_by = Auth()->user()->id;
					$attachment->created_at = Carbon::now();
					$attachment->save();
				}
			}

			DB::commit();

			$gate_in_data['number'] = $gate_log->number;

			$message = 'Greetings from TVS & Sons! Your vehicle ' . $number . ' has arrived in TVS Service Center - ' . Auth::user()->employee->outlet->ax_name . ' at ' . date('d-m-Y h:i A') . $membership_message;

			//Send SMS to Driver
			if (preg_match('/^\d{10}$/', $request->driver_mobile_number)) {
				//Gatein Message
				// $msg = sendSMSNotification($request->driver_mobile_number, $message);
				// Notification::dispatch($request->driver_mobile_number, $message);
				$notifications['notification_type'] = 'SMS';
				$notifications['data'] = ['mobile_no' => $request->driver_mobile_number, 'message' => $message];

				Notification::dispatch($notifications);

				//Tracking Message
				// $msg = sendSMSNotification($request->driver_mobile_number, $tracking_message);
				$notifications['notification_type'] = 'SMS';
				$notifications['data'] = ['mobile_no' => $request->driver_mobile_number, 'message' => $tracking_message];
				Notification::dispatch($notifications);
			}

			//Send SMS to Customer
			if ($job_order->customer) {
				if (preg_match('/^\d{10}$/', $job_order->customer->mobile_no)) {
					//Gatein Message
					// $msg = sendSMSNotification($job_order->customer->mobile_no, $message);
					$notifications['notification_type'] = 'SMS';
					$notifications['data'] = ['mobile_no' => $job_order->customer->mobile_no, 'message' => $message];
					Notification::dispatch($notifications);

					//Tracking Message
					// $msg = sendSMSNotification($job_order->customer->mobile_no, $tracking_message);
					$notifications['notification_type'] = 'SMS';
					$notifications['data'] = ['mobile_no' => $job_order->customer->mobile_no, 'message' => $tracking_message];
					Notification::dispatch($notifications);
				}
			}

			//Check Floating GatePass
			$floating_gate_pass = FloatingGatePass::join('job_cards', 'job_cards.id', 'floating_stock_logs.job_card_id')->join('job_orders', 'job_orders.id', 'job_cards.job_order_id')->where('floating_stock_logs.status_id', 11162)->where('job_orders.vehicle_id', $vehicle->id)->count();

			$gate_in_data['floating_message'] = 0;

			if ($floating_gate_pass) {
				$gate_in_data['floating_message'] = 'This Vehicle is already waiting for float work!';
			}

			$title = 'Inward List';
			$message = 'Vehicle Gate In Completed! Waiting for Vehicle inward';

			// sendPushNotification($title, $message, $redirection_id = 1, $vehicle_data = NULL, $outlet_id = Auth::user()->employee->outlet_id);

			$notifications['notification_type'] = 'PUSH';
			$notifications['data'] = ['title' => $title, 'message' => $message, 'redirection_id' => 1, 'vehicle_data' => NULL, 'outlet_id' => Auth::user()->employee->outlet_id];

			Notification::dispatch($notifications);

			return response()->json([
				'success' => true,
				'gate_log' => $gate_in_data,
				'message' => 'Gate Entry Saved Successfully!!',
			]);

		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}

	}

	public function deleteGateLog(Request $request) {
		// dd($request->all());
		if ($request->id) {
			$gate_log = GateLog::find($request->id);
			if ($gate_log->status_id == 8120) {
				GateLog::where('id', $request->id)->forceDelete();

				return response()->json([
					'success' => true,
					'message' => 'Gatelog Deleted Successfully!!',
				]);
			} else {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'error' => [
						'Gatelog Cannot be deleted!',
					],
				]);
			}
		} else {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'error' => [
					'Gatelog not Found!',
				],
			]);
		}
	}

	//Table List
	public function getGateLogList(Request $request) {
		$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
		array_push($outlet_ids, Auth::user()->employee->outlet_id);

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

		$gate_pass_lists = GateLog::select([
			'gate_logs.id as gate_log_id',
			'gate_logs.number',
			'gate_logs.gate_in_date',
			'gate_logs.status_id',
			'vehicles.registration_number',
			'vehicles.engine_number',
			'vehicles.chassis_number',
			'models.model_name',
			'outlets.code as outlet',
			'regions.name as region', 'states.name as state',
			'configs.name as status',
		])
			->leftjoin('job_orders', 'job_orders.id', 'gate_logs.job_order_id')
			->leftjoin('vehicles', 'vehicles.id', 'job_orders.vehicle_id')
			->leftjoin('models', 'models.id', 'vehicles.model_id')
			->leftjoin('outlets', 'outlets.id', 'job_orders.outlet_id')
			->leftjoin('regions', 'regions.id', 'outlets.region_id')
			->leftjoin('states', 'states.id', 'outlets.state_id')
			->join('configs', 'configs.id', 'gate_logs.status_id')
			->where('gate_logs.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->model_id)) {
					$query->where('vehicles.model_id', $request->model_id);
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->outlet_id)) {
					$query->where('job_orders.outlet_id', $request->outlet_id);
				}
			})

			->where(function ($query) use ($start_date, $end_date) {
				$query->whereDate('gate_logs.created_at', '>=', $start_date)
					->whereDate('gate_logs.created_at', '<=', $end_date);
			});

		if (!Entrust::can('overall-outlet-gatelog')) {
			if (Entrust::can('mapped-outlet-gatelog')) {
				$gate_pass_lists->whereIn('job_orders.outlet_id', $outlet_ids);
			} elseif (Entrust::can('own-outlet-gatelog')) {
				$gate_pass_lists->where('job_orders.outlet_id', Auth::user()->employee->outlet_id);
			} else {
				$gate_pass_lists->where('gate_logs.created_by_id', Auth::user()->id);
			}
		}

		$gate_pass_lists->orderBy('gate_logs.id', 'DESC');

		return Datatables::of($gate_pass_lists)
			->addColumn('status', function ($gate_pass_list) {
				// $status = $gate_pass_list->status == 'Active' ? 'green' : 'red';
				return $gate_pass_list->status;
				// '<span class="status-indigator ' . $status . '"></span>' . $gate_pass_list->status;
			})
			->addColumn('action', function ($gate_pass_list) {
				$img_edit = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img_edit_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';

				// if (Entrust::can('edit-gate-log')) {
				// $output .= '<a href="#!/gate-log/edit/' . $gate_pass_list->id . '" id = "" title="Edit"><img src="' . $img_edit . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img_edit_active . '" onmouseout=this.src="' . $img_edit . '"></a>';
				// }

				if ($gate_pass_list->status_id == 8120) {
					if (Entrust::can('delete-gate-log')) {
						$output .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_gate_log" onclick="angular.element(this).scope().deleteGateLog(' . $gate_pass_list->gate_log_id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>
					';
					}
				}

				return $output;
			})
			->make(true);
	}

	public function getGateLogFilter() {
		$this->data['model_list'] = collect(ModelType::select('id', 'model_name')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'model_name' => 'Select Model Name']);
		$this->data['status'] = collect(Config::select('id', 'name')->where('config_type_id', 37)->get())->prepend(['id' => '', 'name' => 'Select Status']);
		$this->data['outlet_list'] = collect(Outlet::select('id', 'code')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'code' => 'Select Outlet']);

		return response()->json($this->data);
	}

	public function getVehicleSearchList(Request $request) {
		// dd($request->all());
		$key = $request->key;

		$list = Vehicle::with(['kmReadingType'])->select(
			'driver_name',
			'driver_mobile_number',
			'service_contact_number',
			'km_reading_type_id',
			'km_reading',
			'hr_reading',
			'engine_number',
			'chassis_number',
			'registration_number',
			'id'
		)
			->where(function ($q) use ($key) {
				$q->where('engine_number', 'like', $key . '%')
					->orWhere('chassis_number', 'like', '%' . $key . '%')
					->orWhere('registration_number', 'like', '%' . $key . '%')
				;
			})
			// ->whereNotNull('vehicles.sold_date')
			->get();

		// dd($list);
		if (count($list) == 0) {
			$vehicle_data = $this->getSoap->GetVehicleDetails($key);
			// dd($vehicle_data);

			if ($vehicle_data && $vehicle_data['success'] == 'true') {
				// dump($vehicle_data);
				DB::beginTransaction();
				try {
					if(isset($vehicle_data['vehicle_reg_number'])){
						$vehicle = Vehicle::where([
							'company_id' => Auth::user()->company_id,
							'registration_number' => $vehicle_data['vehicle_reg_number'],
						])->first();

						if (!$vehicle) {
							//Chassis Number
							if ($vehicle_data['chassis_number']) {
								$vehicle = Vehicle::firstOrNew([
									'company_id' => Auth::user()->company_id,
									'chassis_number' => $vehicle_data['chassis_number'],
								]);
							}
							//Engine Number
							else {
								$vehicle = Vehicle::firstOrNew([
									'company_id' => Auth::user()->company_id,
									'engine_number' => $vehicle_data['engine_number'],
								]);
							}
						}
						$vehicle->is_registered = 1;
					}
					else
					{
						if ($vehicle_data['chassis_number']) {
							$vehicle = Vehicle::firstOrNew([
								'company_id' => Auth::user()->company_id,
								'chassis_number' => $vehicle_data['chassis_number'],
							]);
						}
						//Engine Number
						else {
							$vehicle = Vehicle::firstOrNew([
								'company_id' => Auth::user()->company_id,
								'engine_number' => $vehicle_data['engine_number'],
							]);
						}
						$vehicle->is_registered = 0;
					}

					if (!$vehicle->exists) {					
						$vehicle->company_id = Auth::user()->company_id;
						$vehicle->created_by_id = Auth::user()->id;
						$vehicle->created_at = Carbon::now();
					} else {
						$vehicle->updated_by_id = Auth::user()->id;
						$vehicle->updated_at = Carbon::now();
					}

					$vehicle->registration_number = isset($vehicle_data['vehicle_reg_number']) ? $vehicle_data['vehicle_reg_number'] : NULL;
					$vehicle->chassis_number = $vehicle_data['chassis_number'];
					$vehicle->engine_number = $vehicle_data['engine_number'];

					$vehicle->driver_mobile_number = $vehicle_data['driver_mobile'];
					$vehicle->service_contact_number =$vehicle_data['service_contact_number'];
					if($vehicle_data['reading_type'] == 'KM'){
						$vehicle->km_reading_type_id = 8040;
						$vehicle->km_reading =$vehicle_data['current_reading'];
					}
					else{
						$vehicle->km_reading_type_id = 8041;
						$vehicle->hr_reading =$vehicle_data['current_reading'];
					}
					
					//Save Customer
					$customer = null;
					if(isset($vehicle_data['al_dms_code'])){
						$customer = Customer::where('code',ltrim($vehicle_data['al_dms_code'], '0'))->first();
						if($customer){
							$vehicle->customer_id = $customer->id;
							$vehicle->is_sold = 1;
						}else{
							$vehicle->is_sold = 0;
						}
					}
					$vehicle->sold_date = date('Y-m-d',strtotime($vehicle_data['vehicle_sales_date']));
					$vehicle->save();

					//Save Vehicle Owner
					if($customer)
					{
						$vehicle_owner = VehicleOwner::firstornew(['vehicle_id' => $vehicle->id, 'customer_id' => $customer->id]);
						
						$ownership_count = VehicleOwner::where('vehicle_id', $vehicle->id)->count();

						if ($vehicle_owner->exists) {
							//Check last owner is same custmer or not
							$last_vehicle_owner = VehicleOwner::where('vehicle_id', $vehicle->id)->orderBy('ownership_id', 'DESC')->first();

							if ($last_vehicle_owner->customer_id != $customer->id) {
								$ownership_id = $last_vehicle_owner->ownership_id + 1;
								$vehicle_owner->ownership_id = $ownership_id;
							}

							$vehicle_owner->from_date = Carbon::now();
							$vehicle_owner->updated_at = Carbon::now();
						} else {
							$ownership_id = 8160 + $ownership_count;
							$vehicle_owner->ownership_id = $ownership_id;
							$vehicle_owner->from_date = Carbon::now();
							$vehicle_owner->created_at = Carbon::now();
						}
						$vehicle_owner->save();
					}
				
					DB::commit();

					$list = Vehicle::with(['kmReadingType'])->select(
						'driver_name',
						'driver_mobile_number',
						'service_contact_number',
						'km_reading_type_id',
						'km_reading',
						'hr_reading',
						'engine_number',
						'chassis_number',
						'registration_number',
						'id'
					)
					->where('id', $vehicle->id)
					->get();
				} catch (Exception $e) {
					DB::rollBack();
					return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
				}
			}
			else
			{
				$list = [];
			}
		}
		return response()->json($list);
	}

	//Card List
	public function getGateInList(Request $request) {
		try {
			$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
			array_push($outlet_ids, Auth::user()->employee->outlet_id);

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

			$vehicle_gate_pass_list = GateLog::select([
				'gate_logs.id as gate_log_id',
				'gate_logs.number',
				'gate_logs.gate_in_date',
				'gate_logs.status_id',
				'vehicles.registration_number',
				'vehicles.engine_number',
				'vehicles.chassis_number',
				'models.model_name',
				'outlets.code as outlet',
				'regions.name as region', 'states.name as state',
				'configs.name as status',
			])
				->leftjoin('job_orders', 'job_orders.id', 'gate_logs.job_order_id')
				->leftjoin('vehicles', 'vehicles.id', 'job_orders.vehicle_id')
				->leftjoin('models', 'models.id', 'vehicles.model_id')
				->leftjoin('outlets', 'outlets.id', 'job_orders.outlet_id')
				->leftjoin('regions', 'regions.id', 'outlets.region_id')
				->leftjoin('states', 'states.id', 'outlets.state_id')
				->join('configs', 'configs.id', 'gate_logs.status_id')

				->where(function ($query) use ($request) {
					if (!empty($request->model_id)) {
						$query->where('vehicles.model_id', $request->model_id);
					}
				})

				->where(function ($query) use ($request) {
					if (!empty($request->outlet_id)) {
						$query->where('job_orders.outlet_id', $request->outlet_id);
					}
				})

				->where(function ($query) use ($start_date, $end_date) {
					$query->whereDate('gate_logs.created_at', '>=', $start_date)
						->whereDate('gate_logs.created_at', '<=', $end_date);
				})

				->where(function ($query) use ($request) {
					if (!empty($request->search_key)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('models.model_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('gate_logs.job_card_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('status.name', 'LIKE', '%' . $request->search_key . '%')
						;
					}
				});

			if (!Entrust::can('overall-outlet-gatelog')) {
				if (Entrust::can('mapped-outlet-gatelog')) {
					$vehicle_gate_pass_list->whereIn('job_orders.outlet_id', $outlet_ids);
				} elseif (Entrust::can('own-outlet-gatelog')) {
					$vehicle_gate_pass_list->where('job_orders.outlet_id', Auth::user()->employee->outlet_id);
				} else {
					$vehicle_gate_pass_list->where('gate_logs.created_by_id', Auth::user()->id);
				}
			}

			$vehicle_gate_pass_list->orderBy('gate_logs.id', 'DESC');

			$total_records = $vehicle_gate_pass_list->get()->count();

			if ($request->offset) {
				$vehicle_gate_pass_list->offset($request->offset);
			}
			if ($request->limit) {
				$vehicle_gate_pass_list->limit($request->limit);
			}

			$vehicle_gate_pass_list = $vehicle_gate_pass_list->get();

			return response()->json([
				'success' => true,
				'vehicle_gate_pass_list' => $vehicle_gate_pass_list,
				'total_records' => $total_records,
			]);

		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

}
