<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoadTestGatePass extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'road_test_gate_pass';
	public $timestamps = true;
	protected $fillable = [
		"company_id",
		"job_order_id",
		"number",
		"gate_in_date",
		"gate_in_remarks",
		"gate_out_date",
		"gate_out_remarks",
	];

	// Getters --------------------------------------------------------------
	public function getGateInDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y h:i A ', strtotime($date));
	}

	public function getGateOutDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y h:i A ', strtotime($date));
	}

	public function jobOrder() {
		return $this->belongsTo('App\JobOrder', 'job_order_id');
	}

	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}
}
