<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class JobOrderEInvoice extends BaseModel
{
    use SeederTrait;
    protected $table = 'job_order_e_invoices';
    public $timestamps = false;
    protected $fillable = [
        "job_order_id",
    ];
}
