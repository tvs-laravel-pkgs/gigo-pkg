<?php

namespace Abs\GigoPkg;

use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatteryApplication extends BaseModel
{
    use SoftDeletes;
    protected $table = 'battery_applications';
    public $timestamps = true;
    protected $fillable = [];
}
