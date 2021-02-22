<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use App\Outlet;
use App\Vehicle;
use App\VehicleModel;
use App\FinancialYear;
use App\Customer;
use App\VehicleDeliveryStatus;
use Abs\SerialNumberPkg\SerialNumberGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPExcel_IOFactory;
use PHPExcel_Shared_Date;
use PHPExcel_Style_NumberFormat;
use Validator;
use Carbon\Carbon;
use DB;

class GateLog extends Model
{
    use SeederTrait;
    use SoftDeletes;
    protected $table = 'gate_logs';
    public $timestamps = true;
    protected $fillable = [
        "company_id",
        "number",
        "date",
        "gate_in_remarks",
        "gate_out_date",
        "gate_out_remarks",
        "gate_pass_id",
        "status_id",
    ];

    //APPEND - INBETWEEN REGISTRATION NUMBER
    public function getRegistrationNumberAttribute($value)
    {
        $registration_number = '';

        if ($value) {
            $value = str_replace('-', '', $value);
            $reg_number = str_split($value);

            $last_four_numbers = substr($value, -4);

            $registration_number .= $reg_number[0] . $reg_number[1] . '-' . $reg_number[2] . $reg_number[3] . '-';

            if (is_numeric($reg_number[4])) {
                $registration_number .= $last_four_numbers;
            } else {
                $registration_number .= $reg_number[4];
                if (is_numeric($reg_number[5])) {
                    $registration_number .= '-' . $last_four_numbers;
                } else {
                    $registration_number .= $reg_number[5] . '-' . $last_four_numbers;
                }
            }
        }
        return $this->attributes['registration_number'] = $registration_number;
    }

    public function getDateOfJoinAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y', strtotime($value));
    }

    public function setDateOfJoinAttribute($date)
    {
        return $this->attributes['date_of_join'] = empty($date) ? null : date('Y-m-d', strtotime($date));
    }

    public function getGateInDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
    }

    public function getGateOutDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
    }

    //issue : naming
    // public function vehicleDetail() {
    //     return $this->belongsTo('App\Vehicle', 'vehicle_id');
    // }

    public function gatePass()
    {
        return $this->belongsTo('App\GatePass', 'gate_pass_id');
    }

    public function createdBy()
    {
        return $this->belongsTo('App\User', 'created_by_id');
    }

    public function jobOrder()
    {
        return $this->belongsTo('App\JobOrder', 'job_order_id');
    }
    public function status()
    {
        return $this->belongsTo('App\Config', 'status_id');
    }

    public function outlet()
    {
        return $this->belongsTo('App\Outlet', 'outlet_id');
    }

    public function driverAttachment()
    {
        return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 225)->where('attachment_type_id', 249);
    }
    public function kmAttachment()
    {
        return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 225)->where('attachment_type_id', 248);
    }
    public function vehicleAttachment()
    {
        return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 225)->where('attachment_type_id', 247);
    }
    public function chassisAttachment()
    {
        return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 225)->where('attachment_type_id', 236);
    }

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

    public static function createFromObject($record_data)
    {

        $errors = [];
        $company = Company::where('code', $record_data->company)->first();
        if (!$company) {
            dump('Invalid Company : ' . $record_data->company);
            return;
        }

        $admin = $company->admin();
        if (!$admin) {
            dump('Default Admin user not found');
            return;
        }

        $type = Config::where('name', $record_data->type)->where('config_type_id', 89)->first();
        if (!$type) {
            $errors[] = 'Invalid Tax Type : ' . $record_data->type;
        }

        if (count($errors) > 0) {
            dump($errors);
            return;
        }

        $record = self::firstOrNew([
            'company_id' => $company->id,
            'name' => $record_data->tax_name,
        ]);
        $record->type_id = $type->id;
        $record->created_by_id = $admin->id;
        $record->save();
        return $record;
    }

    public static function getList($params = [], $add_default = true, $default_text = 'Select Gate Log')
    {
        $list = Collect(Self::select([
            'id',
            'name',
        ])
                ->orderBy('name')
                ->get());
        if ($add_default) {
            $list->prepend(['id' => '', 'name' => $default_text]);
        }
        return $list;
    }

    public static function validate_format_record($import_record, $mandatory_columns, $job)
    {
        // dd($import_record);
        $skip = false;
        $success = true;
        $record_errors = [];
        $data = [];
        $status = [];

        if (empty($import_record[$mandatory_columns['Outlet Code']->excel_column_name])) {
            $record_errors[] = 'Outlet Code is empty';
            $skip = true;
        } else {
            $outlet_code = $import_record[$mandatory_columns['Outlet Code']->excel_column_name];

            $outlet = Outlet::where('code', $outlet_code)->where('is_store', 1)->first();
            if ($outlet) {
                $data['outlet_id'] = $outlet->id;
            } else {
                $record_errors[] = $import_record[$mandatory_columns['Outlet Code']->excel_column_name] . ' outlet not mapped in GIGO';
                $skip = true;
            }
        }

        $vehicle = '';
        if (empty($import_record[$mandatory_columns['Engine Number']->excel_column_name]) || empty($import_record[$mandatory_columns['Chassis Number']->excel_column_name])) {
            $record_errors[] = 'Chassis / Engine Number is empty';
            $skip = true;
        } else {
            if (!empty($import_record[$mandatory_columns['Vehicle Registration Number']->excel_column_name])) {
                $vehicle = Vehicle::where([
                    'company_id' => $job->company_id,
                    'registration_number' => $import_record[$mandatory_columns['Vehicle Registration Number']->excel_column_name],
                ])->first();

                if (!$vehicle) {
                    //Chassis Number
                    if ($import_record[$mandatory_columns['Chassis Number']->excel_column_name]) {
                        $vehicle = Vehicle::firstOrNew([
                            'company_id' => $job->company_id,
                            'chassis_number' => $import_record[$mandatory_columns['Chassis Number']->excel_column_name],
                        ]);
                    }
                    //Engine Number
                    else {
                        $vehicle = Vehicle::firstOrNew([
                            'company_id' => $job->company_id,
                            'engine_number' => $import_record[$mandatory_columns['Engine Number']->excel_column_name],
                        ]);
                    }
                }
                $is_registered = 1;
            } else {
                //Chassis Number
                if ($import_record[$mandatory_columns['Vehicle Registration Number']->excel_column_name]) {
                    $vehicle = Vehicle::firstOrNew([
                        'company_id' => $job->company_id,
                        'chassis_number' => $import_record[$mandatory_columns['Chassis Number']->excel_column_name],
                    ]);
                }
                //Engine Number
                else {
                    $vehicle = Vehicle::firstOrNew([
                        'company_id' => $job->company_id,
                        'engine_number' => $import_record[$mandatory_columns['Engine Number']->excel_column_name],
                    ]);
                }
                $is_registered = 0;
            }

            $vehicle->is_registered = $is_registered;
            $vehicle->engine_number = $import_record[$mandatory_columns['Engine Number']->excel_column_name];
            $vehicle->chassis_number = $import_record[$mandatory_columns['Chassis Number']->excel_column_name];
            $vehicle->registration_number = empty($import_record[$mandatory_columns['Vehicle Registration Number']->excel_column_name]) ? null : $import_record[$mandatory_columns['Vehicle Registration Number']->excel_column_name];
            $vehicle->save();
        }

        //Check Model
        if (empty($import_record[$mandatory_columns['Model Number']->excel_column_name]) || empty($import_record[$mandatory_columns['Model Name']->excel_column_name])) {
            $record_errors[] = 'Model Name / Number is empty';
            $skip = true;
        } else {
            $model = VehicleModel::where('model_name', $import_record[$mandatory_columns['Model Name']->excel_column_name])->where('model_number', $import_record[$mandatory_columns['Model Number']->excel_column_name])->first();
            if ($model) {
                if ($vehicle) {
                    $vehicle->model_id = $model->id;
                    $vehicle->save();
                }
            } else {
                $record_errors[] = $import_record[$mandatory_columns['Model Name']->excel_column_name] . ' / ' . $import_record[$mandatory_columns['Model Number']->excel_column_name] . ' Model Name,Model Number no found';
            }
        }

        //Check Reading Type
        if (empty($import_record[$mandatory_columns['KM Reading Type']->excel_column_name]) || empty($import_record[$mandatory_columns['KM/Hrs Reading']->excel_column_name])) {
            $record_errors[] = 'KM Reading Type / KM-Hrs Reading is empty';
            $skip = true;
        } else {
            if ($vehicle) {
                if ($import_record[$mandatory_columns['KM Reading Type']->excel_column_name] == 'KM') {
                    $vehicle->km_reading_type_id = 8040;
					$vehicle->km_reading = $import_record[$mandatory_columns['KM/Hrs Reading']->excel_column_name];
                    $vehicle->hr_reading = NULL;
                } else {
                    $vehicle->km_reading_type_id = 8041;
					$vehicle->hr_reading = $import_record[$mandatory_columns['KM/Hrs Reading']->excel_column_name];
                    $vehicle->km_reading = NULL;
                }
                $vehicle->save();
            }
        }

        //Check Customer
        if (empty($import_record[$mandatory_columns['Customer Code']->excel_column_name])) {
            $record_errors[] = 'Customer Code is empty';
            $skip = true;
        } else {
            if ($vehicle) {
                $customer = Customer::where('code', ltrim($import_record[$mandatory_columns['Customer Code']->excel_column_name], '0'))->first();
                if ($customer) {

					$vehicle->customer_id = $customer->id;
					$vehicle->is_sold = 1;

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
					
					$vehicle->save();
                }
            }
		}
		
		//Check Driver 
		if (!empty($import_record[$mandatory_columns['Driver Name']->excel_column_name])) {
			if($vehicle)
			{
				$vehicle->driver_name = $import_record[$mandatory_columns['Driver Name']->excel_column_name];
				$vehicle->save();
			}
		}
		if (!empty($import_record[$mandatory_columns['Driver Mobile Number']->excel_column_name])) {
			if($vehicle)
			{
				$vehicle->driver_mobile_number = $import_record[$mandatory_columns['Driver Mobile Number']->excel_column_name];
				$vehicle->save();
			}
        }

		//Check GateIn time
        if (empty($import_record[$mandatory_columns['Gate In Date & Time']->excel_column_name])) {
            $record_errors[] = 'Gate In Date & Time is empty';
            $skip = true;
        } else {
			$gate_in_date = PHPExcel_Style_NumberFormat::toFormattedString($import_record[$mandatory_columns['Gate In Date & Time']->excel_column_name], PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);

			$gate_in_time = PHPExcel_Style_NumberFormat::toFormattedString($import_record[$mandatory_columns['Gate In Date & Time']->excel_column_name], PHPExcel_Style_NumberFormat::FORMAT_DATE_TIME4);

			$data['gate_in_date_time'] = $gate_in_date .' '. $gate_in_time;
		}

		if($vehicle)
		{
			//CHECK VEHICLE PREVIOUS JOBCARD STATUS
			$previous_job_order = JobOrder::where('vehicle_id', $vehicle->id)->orderBy('id', 'DESC')->first();
			if ($previous_job_order) {
				if ($previous_job_order->status_id != 8470 && $previous_job_order->status_id != 8476 && $previous_job_order->status_id != 8467 && $previous_job_order->status_id != 8468 && $previous_job_order->status_id != '') {
					$record_errors[] = 'Previous Job Order not completed on this Vehicle!';
            		$skip = true;
				}
			}
		}

        if (!empty($import_record[$mandatory_columns['Status']->excel_column_name])) {
            $vehicle_status = VehicleDeliveryStatus::where('name',$import_record[$mandatory_columns['Status']->excel_column_name])->first();
            if($vehicle_status){
                $data['vehicle_status_id'] = $vehicle_status->id;
            }else{
                $data['vehicle_status_id'] = 1;
            }
        }else{
            $data['vehicle_status_id'] = 1;
        }

        if (!$skip && $vehicle) {
            $data['vehicle_id'] = $vehicle->id;
        }
        $status['skip'] = $skip;
        $status['errors'] = $record_errors;
        $status['data'] = $data;
        return $status;
	}
	
	public static function create_gate_in_entry($data)
    {
		//GET BRANCH/OUTLET
		$branch = Outlet::where('id', $data['outlet_id'])->first();

		//GET VEHICLE
		$vehicle = Vehicle::find($data['vehicle_id']);
		if (date('m') > 3) {
			$year = date('Y') + 1;
		} else {
			$year = date('Y');
		}
		//GET FINANCIAL YEAR ID
		$financial_year = FinancialYear::where('from', $year)
			->where('company_id', $branch->company_id)
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
		
		//Save Job Order
		$job_order = new JobOrder;
		$job_order->company_id = $branch->company_id;
		$job_order->vehicle_id = $vehicle->id;
		$job_order->outlet_id = $branch->id;
		$job_order->vehicle_delivery_status_id = $data['vehicle_status_id'];
		if ($vehicle->currentOwner) {
			$job_order->customer_id = $vehicle->currentOwner->customer_id;
		}
		
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
				'unique:job_orders,number,' . $job_order->id . ',id,company_id,' . $branch->company_id,
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
		$job_order->service_advisor_id = NULL;
		$job_order->km_reading = $vehicle->km_reading;
		$job_order->hr_reading = $vehicle->hr_reading;
		$job_order->km_reading_type_id = $vehicle->km_reading_type_id;
		$job_order->driver_name = $vehicle->driver_name;
		$job_order->driver_mobile_number = $vehicle->driver_mobile_number;
		$job_order->status_id = 8460; //Ready for Inward
		$job_order->save();
		// dd($job_order);

		//Save gateLog
		$gate_log = new GateLog;
		$gate_log->company_id = $branch->company_id;
		$gate_log->job_order_id = $job_order->id;
		$gate_log->gate_in_date = $data['gate_in_date_time'];
		$gate_log->status_id = 8120; //GATE IN COMPLETED
		$gate_log->outlet_id = $branch->id;

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
				'unique:gate_logs,number,' . $gate_log->id . ',id,company_id,' . $branch->company_id,
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

		//Inward Process Customer Save
		if($job_order->cusotmer_id)
		{
			$job_order->inwardProcessChecks()->where('tab_id', 8701)->update(['is_form_filled' => 1]);
		}

		//Inward Process Vehicle Save
		$job_order->inwardProcessChecks()->where('tab_id', 8700)->update(['is_form_filled' => 1]);

		return true;
    }

}
