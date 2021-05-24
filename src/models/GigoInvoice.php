<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class GigoInvoice extends BaseModel
{
    use SeederTrait;
    use SoftDeletes;
    protected $table = 'gigo_invoices';
    public $timestamps = true;
    protected $fillable = [
        'company_id',
        'invoice_number',
        'invoice_date',
        'customer_id',
        'invoice_of_id',
        'entity_id',
        'outlet_id',
        'sbu_id',
        'invoice_amount',
        'received_amount',
        'balance_amount',
        'created_by_id',
        'created_at',
    ];

    public function getCreatedAtAttribute($date)
    {
        return empty($date) ? '' : date('d-m-Y h:i A ', strtotime($date));
    }

    public function invoiceItems()
    {
        return $this->hasMany('Abs\GigoPkg\GigoInvoiceItem', 'invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo('App\Customer', 'customer_id');
    }

    public function outlet()
    {
        return $this->belongsTo('App\Outlet', 'outlet_id');
    }

    public function sbu()
    {
        return $this->belongsTo('App\Sbu', 'sbu_id');
    }

    public function saleOrder()
    {
        return $this->belongsTo('App\SaleOrder', 'entity_id');
    }
}
