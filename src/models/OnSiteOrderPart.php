<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class OnSiteOrderPart extends BaseModel {
	use SeederTrait;
	protected $table = 'on_site_order_parts';
	protected $fillable = [
		"name",
	];

	public function part() {
		return $this->belongsTo('App\Part', 'part_id');
	}
	public function splitOrderType() {
		return $this->belongsTo('App\SplitOrderType', 'split_order_type_id');
	}
	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}
}
