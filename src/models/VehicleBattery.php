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
        ["company_id", "business_id", "vehicle_id", "customer_id", "battery_make_id", "manufactured_date"]
    ;

    public function getManufacturedDateAttribute($value)
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
}
