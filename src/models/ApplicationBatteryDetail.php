<?php

namespace Abs\GigoPkg;

use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationBatteryDetail extends BaseModel
{
    use SoftDeletes;
    protected $table = 'application_battery_details';
    public $timestamps = true;
    protected $fillable = [];
}
