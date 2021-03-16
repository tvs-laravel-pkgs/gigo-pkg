<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnSiteOrderReturnedPart extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'on_site_order_returned_parts';
	public $timestamps = true;
	protected $fillable = [
		"on_site_order_part_id",
		"returned_qty",
		"returned_to_id",
		"remarks",
	];
}
