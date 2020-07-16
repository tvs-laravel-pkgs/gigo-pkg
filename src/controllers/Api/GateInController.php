<?php

namespace Abs\GigoPkg\Api;

use Abs\GigoPkg\ModelType;
use Abs\SerialNumberPkg\SerialNumberGroup;
use App\Config;
use App\FinancialYear;
use App\GateLog;
use App\Http\Controllers\Controller;
use App\JobOrder;
use App\Employee;
use App\Outlet;
use App\Vehicle;
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

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getFormData() {
		try {
			$extras = [
				'reading_type_list' => Config::getDropDownList([
					'config_type_id' => 33,
					'default_text' => 'Select Reading type',
				]),
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
		DB::beginTransaction();
		try {
			//REMOVE WHITE SPACE BETWEEN REGISTRATION NUMBER
			$request->registration_number = str_replace(' ', '', $request->registration_number);

			//REGISTRATION NUMBER VALIDATION
			$error = '';
			if ($request->registration_number) {
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
					$first_two_string = substr($request->registration_number, 0, 2);
					$next_two_number = substr($request->registration_number, 2, 2);
					$last_two_number = substr($request->registration_number, -2);
					$total_numbers = strlen(preg_replace('/[^0-9]/', '', $request->registration_number));

					if (!preg_match('/^[A-Z]+$/', $first_two_string) || !preg_match('/^[0-9]+$/', $next_two_number) || !preg_match('/^[0-9]+$/', $last_two_number) || $total_numbers > 6) {
						$error = "Please enter valid registration number!";
					}
					//issue : Vijay : wrong logic
					// if (!preg_match('/^[A-Z]+$/', $first_two_string) || !preg_match('/^[0-9]+$/', $next_two_number) || !preg_match('/^[0-9]+$/', $last_two_number)) {
					// 	$error = "Please enter valid registration number!";
					// }
					if ($error) {
						return response()->json([
							'success' => false,
							'error' => 'Validation Error',
							'errors' => [
								$error,
							],
						]);
					}
				}
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
					'max:10',
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
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			//VEHICLE GATE ENTRY DETAILS
			// UNREGISTRED VEHICLE DIFFERENT FLOW WAITING FOR REQUIREMENT
			if (!$request->is_registered == 1) {
				return response()->json([
					'success' => true,
					'message' => 'Unregistred Vehile Not allow!!',
				]);
			}

			//ONLY FOR REGISTRED VEHICLE
			$vehicle = Vehicle::firstOrNew([
				'company_id' => Auth::user()->company_id,
				'registration_number' => $request->registration_number,
			]);
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
			$vehicle->save();
			$request->vehicle_id = $vehicle->id;
			//VEHICLE DETAIL VALIDATION
			$validator1 = Validator::make($request->all(), [
				'chassis_number' => [
					'required',
					'min:10',
					'max:64',
					'string',
					'unique:vehicles,chassis_number,' . $request->vehicle_id . ',id,company_id,' . Auth::user()->company_id,
				],
				// 'vin_number' => [
				// 	'required',
				// 	'min:17',
				// 	'max:32',
				// 	'string',
				// 	'unique:vehicles,vin_number,' . $request->vehicle_id . ',id,company_id,' . Auth::user()->company_id,
				// ],
			]);

			if ($validator1->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator1->errors()->all(),
				]);
			}
			$vehicle->fill($request->all());
			$vehicle->save();

			//CHECK VEHICLE PREVIOUS JOBCARD STATUS
			$previous_job_order = JobOrder::where('vehicle_id', $vehicle->id)->orderBy('id', 'DESC')->first();
			if ($previous_job_order) {
				if ($previous_job_order->status_id != 8468) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Previous Job Order not completed!',
						],
					]);
				}
			}

			$job_order = new JobOrder;
			$job_order->company_id = Auth::user()->company_id;
			$job_order->number = rand();
			$job_order->fill($request->all());
			$job_order->vehicle_id = $vehicle->id;
			$job_order->outlet_id = Auth::user()->employee->outlet_id;
			$job_order->status_id = 8460; //Ready for Inward
			$job_order->save();
			$job_order->number = 'JO-' . $job_order->id;
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

			DB::commit();
			$gate_in_data['number'] = $gate_log->number;
			$gate_in_data['registration_number'] = $vehicle->registration_number;

			//Send SMS to Driver
			if ($request->driver_mobile_number) {
				$message = 'Dear Customer,Gatein entry created for ' . $vehicle->registration_number . ' at ' . Auth::user()->employee->outlet->ax_name;
				$msg = sendSMSNotification($request->driver_mobile_number, $message);
			}

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
	public function getGateLogList(Request $request) {
		$employee_outlet = Employee::with(['employee_outlets'])->find(Auth::user()->id);
		$emp_outlet = array();
		foreach ($employee_outlet->employee_outlets as $outlet) {
			array_push($emp_outlet,[$outlet->id]);
		}
		 $outlet = array(); 
		  foreach ($emp_outlet as $key => $value) { 
		    if (is_array($value)) { 
		      $outlet = array_merge($outlet, array_flatten($value)); 
		    } 
		    else { 
		      $outlet[$key] = $value; 
		    } 
		  } 

		/*if ($request->date_range) {
			$date_range = explode(' to ', $request->date_range);
			$start_date = date('Y-m-d', strtotime($date_range[0]));
			$start_date = $start_date . ' 00:00:00';

			$end_date = date('Y-m-d', strtotime($date_range[1]));
			$end_date = $end_date . ' 23:59:59';
		} else {
			$start_date = date('Y-m-01 00:00:00');
			$end_date = date('Y-m-t 23:59:59');
		}*/
		
		$date = explode('to', $request->date_range);

		$gate_pass_lists = GateLog::select([
			'gate_logs.id as gate_log_id',
			'gate_logs.number',
			'gate_logs.gate_in_date',
			'gate_logs.status_id',
			'vehicles.registration_number',
			'models.model_name',
			'outlets.code as outlet',
			'regions.name as region','states.name as state',
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
			/*->where(function ($query) use ($start_date) {
				if (!empty($start_date)) {
					$query->where('gate_logs.created_at', '>=', $start_date);
				}
			})

			->where(function ($query) use ($end_date) {
				if (!empty($end_date)) {
					$query->where('gate_logs.created_at', '<=', $end_date);
				}
			});*/
			->where(function ($query) use ($request, $date) {
				if (!empty($request->get('date_range'))) {
					$query->whereDate('gate_logs.created_at', '>=', date('Y-m-d', strtotime($date[0])))
						->whereDate('gate_logs.created_at', '<=', date('Y-m-d', strtotime($date[1])));
				}
			});

            if (!Entrust::can('all')) 
            {
	            if(Entrust::can('mapped-outlet'))
				{
				   $gate_pass_lists->whereIn('job_orders.outlet_id', $outlet);
				}
				else if(Entrust::can('own-outlet'))
				{
				   $gate_pass_lists->where('job_orders.outlet_id', Auth::user()->employee->outlet_id);
				}
				else{
				    $gate_pass_lists->where('gate_logs.created_by_id', Auth::user()->id);
				}
			
			}

			//->orderBy('gate_logs.id', 'DESC');
			

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
		$this->data['outlet_list'] = collect(Outlet::select('id', 'code')->where('company_id',  Auth::user()->company_id)->get())->prepend(['id' => '', 'code' => 'Select Outlet']);

		return response()->json($this->data);

	}

}
