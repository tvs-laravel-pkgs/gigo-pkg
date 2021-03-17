<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnSiteOrderTimeLog extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'on_site_order_time_logs';
	public $timestamps = true;
	protected $fillable =
		["on_site_order_id", "work_log_type_id", "start_date_time", "end_date_time"]
	;

	public function getStartDateTimeAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
	}

	public function getEndDateTimeAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
	}

}