<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatteryLoadTestResult extends BaseModel
{
    use SeederTrait;
    use SoftDeletes;
    protected $table = 'battery_load_test_results';
    public $timestamps = true;
    protected $fillable =
        ["company_id",
        "outlet_id",
        "vehicle_battery_id",
        "amp_hour",
        "battery_voltage",
        "load_test_status_id", 
        "hydrometer_electrolyte_status_id",
        "remarks",
        "replaced_battery_make_id",
        "battery_not_replaced_reason_id",
        "multimeter_test_status_id",
        "first_battery_amp_hour_id",
        "second_battery_amp_hour_id",
        "first_battery_battery_voltage_id",
        "second_battery_battery_voltage_id",
        "second_battery_overall_status_id",
        "replaced_second_battery_make_id"
        ]
    ;

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

    public function vehicleBattery()
    {
        return $this->belongsTo('App\VehicleBattery', 'vehicle_battery_id');
    }

    public function outlet()
    {
        return $this->belongsTo('App\Outlet', 'outlet_id');
    }

    public function batteryLoadTestStatus()
    {
        return $this->belongsTo('App\BatteryLoadTestStatus', 'overall_status_id');
    }

    public function loadTestStatus()
    {
        return $this->belongsTo('App\LoadTestStatus', 'load_test_status_id');
    }

    public function hydrometerElectrolyteStatus()
    {
        return $this->belongsTo('App\HydrometerElectrolyteStatus', 'hydrometer_electrolyte_status_id');
    }
    public function replacedBatteryMake(){
        return $this->belongsTo('App\BatteryMake', 'replaced_battery_make_id');
    }
    public function batteryNotReplacedReason() {
		return $this->belongsTo('App\Config','battery_not_replaced_reason_id');
	}

    public function getJobCardDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y', strtotime($value));
    }

    public function getInvoiceDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y', strtotime($value));
    }
    //Battery MultimeterStatus
    public function multimeterTestStatus()
    {
        return $this->belongsTo('App\MultimeterTestStatus', 'multimeter_test_status_id');
    }
   
    //Battery Amphour
    public function firstbatteryAmphour() {
		return $this->belongsTo('App\Config','first_battery_amp_hour_id');
	}
    public function secondbatteryAmphour() {
		return $this->belongsTo('App\Config','second_battery_amp_hour_id');
	}
    //Battery Voltage 
    public function firstbatteryvoltage() {
		return $this->belongsTo('App\Config','first_battery_battery_voltage_id');
	}
    public function secondbatteryvoltage() {
		return $this->belongsTo('App\Config','second_battery_battery_voltage_id');
	}
    //Load Test Battery 2
    public function secondbatteryloadTestStatus()
    {
        return $this->belongsTo('App\LoadTestStatus', 'second_battery_load_test_status_id');
    }

    public function secondbatteryhydrometerElectrolyteStatus()
    {
        return $this->belongsTo('App\HydrometerElectrolyteStatus', 'second_battery_hydrometer_electrolyte_status_id');
    }
    public function secondbatterymultimeterTestStatus()
    {
        return $this->belongsTo('App\MultimeterTestStatus', 'second_battery_multimeter_test_status_id');
    }
    public function secondreplacedBatteryMake(){
        return $this->belongsTo('App\BatteryMake', 'replaced_second_battery_make_id');
    }
    public function secondbatteryNotReplacedReason() {
		return $this->belongsTo('App\Config','second_battery_not_replaced_reason_id');
	}
    public function secondbatteryoverallLoadTestStatus()
    {
        return $this->belongsTo('App\BatteryLoadTestStatus', 'second_battery_overall_status_id');
    }
   
}
