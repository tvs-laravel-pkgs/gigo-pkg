<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class OnSiteOrderRepairOrder extends BaseModel {
	use SeederTrait;
	protected $table = 'on_site_order_repair_orders';
	protected $fillable = [
		"name",
	];

	public function repairOrder() {
		return $this->belongsTo('Abs\GigoPkg\RepairOrder', 'repair_order_id');
	}

	public function splitOrderType() {
		return $this->belongsTo('App\SplitOrderType', 'split_order_type_id');
	}

	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}
}
