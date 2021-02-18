<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class JobOrderPaymentDetail extends BaseModel
{
    use SeederTrait;
    protected $table = 'job_order_payment_details';
    public $timestamps = false;
    protected $fillable = [
        "job_order_id", "transaction_number", "transaction_date", "amount",
    ];

    public function getTransactionDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y', strtotime($value));
    }

    public function paymentMode()
    {
        return $this->belongsTo('App\PaymentMode', 'payment_mode_id');
    }
}
