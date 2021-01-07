<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class InvoiceType extends BaseModel {
	use SeederTrait;
	protected $table = 'invoice_types';
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
