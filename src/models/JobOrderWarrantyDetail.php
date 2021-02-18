<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class JobOrderWarrantyDetail extends BaseModel
{
    use SeederTrait;
    protected $table = 'job_order_warranty_details';
    public $timestamps = false;
    protected $fillable = [
        "job_order_id", "number", "warranty_date", "labour_amount", "parts_amount",
    ];

    public function getWarrantyDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y', strtotime($value));
    }

}
