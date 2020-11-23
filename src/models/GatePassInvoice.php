<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class GatePassInvoice extends BaseModel {
	use SeederTrait;
	protected $table = 'gate_pass_invoices';
	public $timestamps = false;
	protected $fillable = ["id", "gate_pass_id", "invoice_number", "invoice_amount", "invoice_date"];

	public function getInvoiceDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y', strtotime($date));
	}
}
