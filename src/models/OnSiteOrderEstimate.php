<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnSiteOrderEstimate extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'on_site_order_estimates';
	public $timestamps = true;
	protected $fillable =
		["on_site_order_id", "number", "status_id"]
	;

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}
}
