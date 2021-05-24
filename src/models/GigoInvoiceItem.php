<?php

namespace Abs\GigoPkg;

use App\BaseModel;

class GigoInvoiceItem extends BaseModel
{

    protected $table = 'gigo_invoice_items';
    public $timestamps = false;

    public function taxes()
    {
        return $this->belongsToMany('App\Tax', 'gigo_invoice_item_tax', 'invoice_item_id', 'tax_id')->withPivot(['percentage', 'amount']);
    }
}
