<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleBattery extends BaseModel
{
    use SeederTrait;
    use SoftDeletes;
    protected $table = 'vehicle_batteries';
    public $timestamps = true;
    protected $fillable =
        ["company_id", "business_id", "vehicle_id", "customer_id", "battery_make_id", "manufactured_date", "second_battery_make_id", "second_battery_manufactured_date", "battery_status_id", "second_battery_overall_status_id", "outlet_id", "remarks"]
    ;

    public function getJobCardDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y', strtotime($value));
    }

    public function getInvoiceDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y', strtotime($value));
    }

    public function getManufacturedDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y', strtotime($value));
    }

    public function getSecondBatteryManufacturedDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y', strtotime($value));
    }

    public function batteryLoadTestResult()
    {
        return $this->hasMany('App\BatteryLoadTestResult', 'vehicle_battery_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo('App\Customer', 'customer_id');
    }
    public function vehicle()
    {
        return $this->belongsTo('App\Vehicle', 'vehicle_id');
    }
    public function batteryMake()
    {
        return $this->belongsTo('App\BatteryMake', 'battery_make_id');
    }
    public function outlet()
    {
        return $this->belongsTo('App\Outlet', 'outlet_id');
    }
    public function batteryStatus()
    {
        return $this->belongsTo('App\Config', 'battery_status_id');
    }

    public function secondBatteryMake()
    {
        return $this->belongsTo('App\BatteryMake', 'second_battery_make_id');
    }

    public function appModel() {
        return $this->belongsTo('App\VehicleModel', 'app_model_id');
    }

}
