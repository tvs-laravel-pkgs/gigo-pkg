<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class PaymentMode extends BaseModel {
	use SeederTrait;
	protected $table = 'payment_modes';
	public $timestamps = false;
	protected $fillable = [
		"company_id", "code", "name"
	];
}
