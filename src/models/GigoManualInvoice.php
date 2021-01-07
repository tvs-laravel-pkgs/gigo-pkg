<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class GigoManualInvoice extends BaseModel {
	use SeederTrait;
	protected $table = 'gigo_manual_invoices';
	public $timestamps = false;
	protected $fillable = [
		"number", "customer_id", "invoiceable_type", "invoiceable_id", "invoice_type_id", "outlet_id", "amount", "payment_status_id", "receipt_id",
	];

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
	}

	public function getInvoiceDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function invoiceable()
    {
        return $this->morphTo();
    }
}
