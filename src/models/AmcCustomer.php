<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmcCustomer extends Model
{
    use SeederTrait;
    use SoftDeletes;
    protected $table = 'amc_customers';
    public $timestamps = true;
    protected $fillable = [
        "amc_customer_type_id",
        "customer_id",
        "tvs_one_customer_code",
    ];

    public function getStartDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y', strtotime($value));
    }

    public function getExpiryDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y', strtotime($value));
    }

    public function amcMember()
    {
        return $this->hasMany('App\AmcMember', 'amc_customer_id');
    }

    public function amcAggreagteCoupon()
    {
        return $this->hasMany('App\AmcAggregateCoupon', 'amc_customer_id');
    }

    public function activeAmcAggreagteCoupon()
    {
        return $this->hasMany('App\AmcAggregateCoupon', 'amc_customer_id')->where('status_id', 1);
    }

}
