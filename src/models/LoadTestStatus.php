<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class LoadTestStatus extends BaseModel
{
    use SeederTrait;
    public $timestamps = false;
    protected $table = 'load_test_statuses';
    protected $fillable =
        ["company_id", "code", "name"]
    ;

}
