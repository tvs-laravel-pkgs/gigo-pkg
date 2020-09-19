<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OSLWorkOrder extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'osl_work_orders';
	public $timestamps = true;
	protected $fillable = [
		"company_id",
		"number",
		"job_card_id",
		"vendor_id",
		"work_order_description",
		"vendor_contact_no",
	];

	public function vendor() {
		return $this->belongsTo('App\Vendor', 'vendor_id');
	}
}
