<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOrderEstimate extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'job_order_estimates';
	public $timestamps = true;
	protected $fillable =
		["job_order_id", "number", "status_id"]
	;

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}
}
