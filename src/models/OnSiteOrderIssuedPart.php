<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnSiteOrderIssuedPart extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'on_site_order_issued_parts';
	public $timestamps = true;
	protected $fillable = [
		"on_site_order_part_id",
		"issued_qty",
		"issued_mode_id",
		"issued_to_id",
	];

	public function onSiteOrderPart() {
		return $this->belongsTo('Abs\GigoPkg\JobOrderPart', 'job_order_part_id');
	}

	public function issuedTo() {
		return $this->belongsTo('App\User', 'issued_to_id');
	}

	public function issueMode() {
		return $this->belongsTo('App\Config', 'issued_mode_id');
	}
}
