<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class MultimeterTestStatus extends BaseModel
{
    use SeederTrait;
    protected $table = 'multimeter_test_statuses';
    public $timestamps = false;
    protected $fillable =
        ["company_id", "code", "name"]
    ;
}
