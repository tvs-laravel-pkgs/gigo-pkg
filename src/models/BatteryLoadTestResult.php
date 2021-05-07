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
        ["company_id", "outlet_id", "vehicle_battery_id", "load_test_status_id", "hydrometer_electrolyte_status_id", "remarks"]
    ;

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
}
