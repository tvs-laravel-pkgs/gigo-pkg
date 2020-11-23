<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class GatePassCustomer extends BaseModel {
	use SeederTrait;
	protected $table = 'gate_pass_customers';
	public $timestamps = false;
	protected $fillable = ["id", "gate_pass_id", "customer_id", "customer_name", "customer_address"];

	public function customer() {
		return $this->belongsTo('App\Customer', 'customer_id');
	}
}
