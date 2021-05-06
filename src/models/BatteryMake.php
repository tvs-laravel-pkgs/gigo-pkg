<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class BatteryMake extends BaseModel
{
    use SeederTrait;
    protected $table = 'battery_makes';
    public $timestamps = false;
    protected $fillable =
        ["company_id", "code", "name"]
    ;
}
