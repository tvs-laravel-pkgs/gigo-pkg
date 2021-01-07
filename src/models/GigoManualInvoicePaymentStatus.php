<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class GigoManualInvoicePaymentStatus extends BaseModel {
	use SeederTrait;
	protected $table = 'gigo_manual_invoice_payment_statuses';
	public $timestamps = false;
	protected $fillable = [
		"id", "name",
	];

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
	}

	public function getExpiredAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
	}
}
