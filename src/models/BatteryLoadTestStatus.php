<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class BatteryLoadTestStatus extends BaseModel
{
    use SeederTrait;
    protected $table = 'battery_load_test_statuses';
    public $timestamps = false;
    protected $fillable =
        ["company_id", "code", "name"]
    ;
}
