<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class HydrometerElectrolyteStatus extends BaseModel
{
    use SeederTrait;
    protected $table = 'hydrometer_electrolyte_statuses';
    public $timestamps = false;
    protected $fillable =
        ["company_id", "code", "name"]
    ;
}
