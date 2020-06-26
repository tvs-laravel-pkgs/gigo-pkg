<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GatePassDetail extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'gate_pass_details';
	public $timestamps = true;
	protected $fillable = [
		"gate_pass_id",
		"vendor_type_id",
		"vendor_id",
		"work_order_no",
		"work_order_description",
	];

	public function vendorType() {
		return $this->belongsTo('App\Config', 'vendor_type_id');
	}

	public function vendor() {
		return $this->belongsTo('App\Vendor', 'vendor_id');
	}
}
