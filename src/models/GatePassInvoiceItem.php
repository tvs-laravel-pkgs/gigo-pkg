<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class GatePassInvoiceItem extends BaseModel {
	use SeederTrait;
	protected $table = 'gate_pass_invoice_items';
	public $timestamps = false;

	public function part() {
		return $this->belongsTo('App\Part', 'entity_id');
	}

	public function category() {
		return $this->belongsTo('App\Config', 'category_id');
	}
}
