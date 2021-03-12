<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class OnSiteOrderStatus extends BaseModel {
	use SeederTrait;
	protected $table = 'on_site_order_statuses';
	public $timestamps = false;
	protected $fillable = [
		"name",
	];
}
