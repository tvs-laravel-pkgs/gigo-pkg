<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;

class AmcAggregateCoupon extends Model
{
    use SeederTrait;
    protected $table = 'amc_aggregate_coupons';
    public $timestamps = false;
    protected $fillable = [
        "amc_customer_id",
        "coupon_code",
        "status_id",
    ];
}
