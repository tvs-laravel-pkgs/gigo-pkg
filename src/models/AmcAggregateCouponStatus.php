<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;

class AmcAggregateCouponStatus extends Model
{
    use SeederTrait;
    public $timestamps = false;

    protected $table = 'amc_aggregate_coupon_statuses';
    protected $fillable = [
        "code",
        "name",
    ];

}
